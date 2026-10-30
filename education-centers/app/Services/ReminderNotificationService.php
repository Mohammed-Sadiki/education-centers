<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Schedule;
use App\Models\TeacherSalary;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Rappels automatiques basés UNIQUEMENT sur des données réelles enregistrées
 * (dates d'échéance de factures, dates de versement de salaires, séances
 * passées sans présence). Chaque méthode renvoie le nombre de notifications
 * réellement créées et se protège contre les doublons / exécutions répétées.
 */
class ReminderNotificationService
{
    public function __construct(protected NotificationService $notifications) {}

    /**
     * Échéance de paiement proche : factures non réglées dont la due_date réelle
     * tombe dans les prochains jours.
     */
    public function paymentDueSoon(): int
    {
        $days = (int) config('notifications.due_soon_days', 3);
        $today = Carbon::today();
        $created = 0;

        $invoices = Invoice::with('student')
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['paid', 'cancelled', 'draft'])
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $today->copy()->addDays($days))
            ->get();

        foreach ($invoices as $invoice) {
            $remaining = $invoice->remaining_amount;
            if ($remaining <= 0.01) {
                continue; // déjà soldée : rien à annoncer
            }

            $inDays = $today->diffInDays(Carbon::parse($invoice->due_date), false);
            $when = $inDays <= 0
                ? __('aujourd\'hui')
                : __('dans :n jour(s)', ['n' => (int) $inDays]);

            $created += $this->notifyCenterAdmins(
                (int) $invoice->center_id,
                'payment_due',
                __('Paiement à venir'),
                __('Une échéance de :amount arrive :when pour :name.', [
                    'amount' => format_price($remaining, $invoice->currency),
                    'when'   => $when,
                    'name'   => $invoice->student?->full_name ?? '—',
                ]),
                route('super-admin.finance.recovery'),
                Invoice::class,
                $invoice->id,
                metadata: ['amount' => $remaining, 'due_date' => $invoice->due_date?->toDateString()],
            );
        }

        return $created;
    }

    /**
     * Paiement en retard : échéance réellement dépassée et restant impayée.
     * Anti-répétition : pas de nouveau rappel pendant `overdue_cooldown_days`.
     */
    public function paymentOverdue(): int
    {
        $cooldown = (int) config('notifications.overdue_cooldown_days', 15);
        $today = Carbon::today();
        $created = 0;

        $invoices = Invoice::with('student')
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['paid', 'cancelled', 'draft'])
            ->whereDate('due_date', '<', $today)
            ->get();

        foreach ($invoices as $invoice) {
            $remaining = $invoice->remaining_amount;
            if ($remaining <= 0.01) {
                continue;
            }

            $created += $this->notifyCenterAdmins(
                (int) $invoice->center_id,
                'payment_overdue',
                __('Paiement en retard'),
                __('Retard de paiement : reste :amount dû pour :name (échéance du :date).', [
                    'amount' => format_price($remaining, $invoice->currency),
                    'name'   => $invoice->student?->full_name ?? '—',
                    'date'   => Carbon::parse($invoice->due_date)->format('d/m/Y'),
                ]),
                route('super-admin.finance.recovery'),
                Invoice::class,
                $invoice->id,
                $cooldown,
                metadata: ['amount' => $remaining, 'due_date' => $invoice->due_date?->toDateString()],
            );
        }

        return $created;
    }

    /**
     * Salaire à payer : date de versement prévue (scheduled_payment_date) réelle
     * approchant, solde non encore versé. Jamais d'alerte pour un salaire réglé.
     */
    public function salaryDueSoon(): int
    {
        $days = (int) config('notifications.salary_soon_days', 5);
        $today = Carbon::today();
        $created = 0;

        $salaries = TeacherSalary::with('teacher')
            ->whereNotNull('scheduled_payment_date')
            ->whereNotIn('status', ['paid'])
            ->whereDate('scheduled_payment_date', '>=', $today)
            ->whereDate('scheduled_payment_date', '<=', $today->copy()->addDays($days))
            ->get();

        foreach ($salaries as $salary) {
            $remaining = $salary->remaining_amount;
            if ($remaining <= 0.01) {
                continue; // soldé : pas de fausse alerte
            }

            $created += $this->notifyCenterAdmins(
                (int) $salary->center_id,
                'salary',
                __('Salaire à payer'),
                __('Le salaire de :name (:amount) est à verser le :date.', [
                    'name'   => $salary->teacher?->full_name ?? '—',
                    'amount' => format_price($remaining, $salary->currency),
                    'date'   => Carbon::parse($salary->scheduled_payment_date)->format('d/m/Y'),
                ]),
                route('super-admin.finance.teachers'),
                TeacherSalary::class,
                $salary->id,
                metadata: ['amount' => $remaining],
            );
        }

        return $created;
    }

    /**
     * Présences non enregistrées : séance réellement passée (date antérieure à
     * aujourd'hui), non annulée, sans aucune présence saisie → alerte le
     * professeur concerné. Anti-répétition de 7 jours.
     */
    public function missingAttendance(): int
    {
        $today = Carbon::today();
        $created = 0;

        $schedules = Schedule::with(['teacher.user', 'classRoom'])
            ->whereNotNull('teacher_id')
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('date', '<', $today)
            ->whereDate('date', '>=', $today->copy()->subDays(15))
            ->get();

        foreach ($schedules as $schedule) {
            $hasAttendance = Attendance::where('schedule_id', $schedule->id)->exists();
            if ($hasAttendance) {
                continue; // déjà enregistrées : pas d'alerte
            }

            $user = $schedule->teacher?->user;
            if (! $user) {
                continue;
            }

            if ($this->notifications->existsRecently($user, 'attendance', Schedule::class, $schedule->id, 7)) {
                continue;
            }

            $this->notifications->send(
                $user,
                'attendance',
                __('Présences à enregistrer'),
                __('Les présences de la séance du :date n\'ont pas été enregistrées.', [
                    'date' => Carbon::parse($schedule->date)->format('d/m/Y'),
                ]),
                route('teacher.schedule'),
                'bi-clipboard',
                $schedule->center_id,
                Schedule::class,
                $schedule->id,
            );
            $created++;
        }

        return $created;
    }

    /**
     * Diffuse une alerte aux Admin Centre d'un centre avec protection anti-
     * doublon : ignorer si une notification identique non lue existe déjà ; si
     * $cooldownDays > 0, ignorer également si une notification identique a été
     * créée récemment (même lue) pour éviter les rappels journaliers.
     *
     * @return int nombre créé
     */
    protected function notifyCenterAdmins(
        int $centerId,
        string $type,
        string $title,
        string $message,
        ?string $link,
        ?string $relatedType,
        ?int $relatedId,
        int $cooldownDays = 0,
        ?array $metadata = null,
    ): int {
        $admins = User::where('center_id', $centerId)
            ->where('role', User::ROLE_CENTER_ADMIN)
            ->get();

        $created = 0;
        foreach ($admins as $admin) {
            if ($cooldownDays > 0
                && $this->notifications->existsRecently($admin, $type, $relatedType, $relatedId, $cooldownDays)) {
                continue;
            }

            if ($this->notifications->sendUnique($admin, $type, $title, $message, $link, 'bi-bell', $centerId, $relatedType, $relatedId, $metadata) !== null) {
                $created++;
            }
        }

        return $created;
    }
}
