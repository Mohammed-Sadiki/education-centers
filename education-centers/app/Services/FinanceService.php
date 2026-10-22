<?php

namespace App\Services;

use App\Http\Controllers\SuperAdminDomainController;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use Illuminate\Support\Collection;

class FinanceService
{
    /**
     * Prix annuels de démonstration par défaut par domaine.
     * Utilisés uniquement si aucune valeur personnalisée n'est enregistrée.
     */
    public const DEFAULT_ANNUAL_PRICES = [
        'sante'            => 15000.00,
        'informatique'     => 14000.00,
        'hotellerie'       => 12000.00,
        'maconnerie'       => 10000.00,
        'design-interieur' => 13000.00,
        'design-graphique' => 12000.00,
    ];

    /**
     * Salaires de démonstration par défaut si non renseigné.
     */
    public const DEFAULT_TEACHER_SALARY = 6000.00;

    /**
     * Années scolaires disponibles.
     */
    public const SCHOOL_YEARS = [
        '2026/2027',
        '2027/2028',
        '2028/2029',
    ];

    /**
     * Libellés des mois en français.
     */
    public const MONTHS_NAMES = [
        1  => 'Janvier',
        2  => 'Février',
        3  => 'Mars',
        4  => 'Avril',
        5  => 'Mai',
        6  => 'Juin',
        7  => 'Juillet',
        8  => 'Août',
        9  => 'Septembre',
        10 => 'Octobre',
        11 => 'Novembre',
        12 => 'Décembre',
    ];

    /**
     * Retourne la liste des domaines valides.
     */
    public static function getDomains(): array
    {
        return SuperAdminDomainController::DOMAINS;
    }

    /**
     * Obtenir le prix annuel d'une formation (configurable, avec fallback démo).
     */
    public static function getFormationAnnualPrice(Formation $formation): float
    {
        if ($formation->annual_price !== null && (float) $formation->annual_price > 0) {
            return (float) $formation->annual_price;
        }

        if ($formation->price !== null && (float) $formation->price > 0) {
            return (float) $formation->price;
        }

        $domain = $formation->domain;
        return self::DEFAULT_ANNUAL_PRICES[$domain] ?? 12000.00;
    }

    /**
     * Obtenir le salaire mensuel d'un professeur (configurable, avec fallback).
     */
    public static function getTeacherMonthlySalary(Teacher $teacher): float
    {
        if ($teacher->monthly_salary !== null && (float) $teacher->monthly_salary > 0) {
            return (float) $teacher->monthly_salary;
        }

        return self::DEFAULT_TEACHER_SALARY;
    }

    /**
     * Calcule la situation financière des étudiants selon les filtres.
     *
     * @return Collection Collection de lignes pour le tableau des étudiants
     */
    public function getStudentFinancialRows(?string $domain = null, string $schoolYear = '2026/2027', ?int $centerId = null): Collection
    {
        // On récupère les inscriptions de l'année scolaire ciblée (ou toutes si 'all')
        $enrollmentsQuery = Enrollment::with(['student.user', 'formation.center', 'center'])
            ->whereIn('status', ['accepted', 'pending', 'under_review']);

        if ($schoolYear !== 'all') {
            $enrollmentsQuery->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)
                  ->orWhereNull('school_year');
            });
        }

        if ($centerId) {
            $enrollmentsQuery->where('center_id', $centerId);
        }

        if ($domain && $domain !== 'all') {
            $enrollmentsQuery->whereHas('formation', function ($q) use ($domain) {
                $q->where('domain', $domain);
            });
        }

        $enrollments = $enrollmentsQuery->get();

        // Récupérer tous les paiements étudiants filtrés par statut paid
        $paymentsQuery = Payment::where('status', 'paid');
        if ($schoolYear !== 'all') {
            $paymentsQuery->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)
                  ->orWhereNull('school_year');
            });
        }
        if ($centerId) {
            $paymentsQuery->where('center_id', $centerId);
        }
        if ($domain && $domain !== 'all') {
            $paymentsQuery->where(function ($q) use ($domain) {
                $q->where('domain', $domain)
                  ->orWhereHas('formation', fn($f) => $f->where('domain', $domain));
            });
        }

        $paymentsGrouped = $paymentsQuery->get()->groupBy('student_id');

        $rows = collect();

        foreach ($enrollments as $enrollment) {
            $student   = $enrollment->student;
            $formation = $enrollment->formation;
            if (! $student || ! $formation) {
                continue;
            }

            $formationDomain = $formation->domain ?? 'informatique';
            $expectedAmount  = self::getFormationAnnualPrice($formation);

            // Additionner tous les paiements enregistrés pour cet étudiant (et cette formation si rattachée)
            $studentPayments = $paymentsGrouped->get($student->id, collect());
            $paidAmount = (float) $studentPayments->sum('amount');

            $remainingAmount = max(0.00, $expectedAmount - $paidAmount);

            // Statuts : Payé, Partiel, Non payé
            if ($paidAmount >= $expectedAmount && $expectedAmount > 0) {
                $status = 'paid';
                $statusLabel = 'Payé';
                $statusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
                $statusLabel = 'Partiel';
                $statusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
            } else {
                $status = 'unpaid';
                $statusLabel = 'Non payé';
                $statusBadge = 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
            }

            $rows->push((object) [
                'enrollment'         => $enrollment,
                'student'            => $student,
                'formation'          => $formation,
                'center'             => $enrollment->center,
                'domain'             => $formationDomain,
                'domain_label'       => self::getDomains()[$formationDomain] ?? ucfirst($formationDomain),
                'annual_price'       => $expectedAmount,
                'paid_amount'        => $paidAmount,
                'remaining_amount'   => $remainingAmount,
                'payment_percentage' => $expectedAmount > 0 ? min(100.0, round(($paidAmount / $expectedAmount) * 100, 1)) : 0.0,
                'status'             => $status,
                'status_label'       => $statusLabel,
                'status_badge'       => $statusBadge,
                'payments'           => $studentPayments,
                'last_payment'       => $studentPayments->sortByDesc('payment_date')->first(),
            ]);
        }

        return $rows;
    }

    /**
     * Calcule la situation des salaires professeurs pour un mois/année donné.
     *
     * @return Collection Collection de lignes pour le tableau des professeurs
     */
    public function getTeacherSalaryRows(?string $domain = null, int $month = 10, int $year = 2026, string $schoolYear = '2026/2027', ?int $centerId = null): Collection
    {
        $teachersQuery = Teacher::with(['user', 'center'])->where('status', 'active');

        if ($centerId) {
            $teachersQuery->where('center_id', $centerId);
        }

        if ($domain && $domain !== 'all') {
            $teachersQuery->where('domain', $domain);
        }

        $teachers = $teachersQuery->get();

        // Récupérer les fiches de salaires créées pour ce mois et cette année
        $salaryRecords = TeacherSalary::where('month', $month)
            ->where('year', $year)
            ->when($centerId, fn($q) => $q->where('center_id', $centerId))
            ->get()
            ->keyBy('teacher_id');

        $rows = collect();

        foreach ($teachers as $teacher) {
            $monthlySalary = self::getTeacherMonthlySalary($teacher);
            $record        = $salaryRecords->get($teacher->id);

            $paidAmount = $record ? (float) $record->paid_amount : 0.00;
            $expected   = $record ? (float) $record->expected_amount : $monthlySalary;
            if ($expected <= 0) {
                $expected = $monthlySalary;
            }

            $remaining = max(0.00, $expected - $paidAmount);

            if ($paidAmount >= $expected && $expected > 0) {
                $status      = 'paid';
                $statusLabel = 'Payé';
                $statusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
            } elseif ($paidAmount > 0) {
                $status      = 'partial';
                $statusLabel = 'Partiel';
                $statusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
            } else {
                $status      = 'unpaid';
                $statusLabel = 'Non payé';
                $statusBadge = 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
            }

            $tDomain = $teacher->domain ?? 'informatique';
            $paidPercentage = $expected > 0 ? min(100.0, round(($paidAmount / $expected) * 100, 1)) : 0.0;

            $rows->push((object) [
                'teacher'          => $teacher,
                'center'           => $teacher->center,
                'domain'           => $tDomain,
                'domain_label'     => self::getDomains()[$tDomain] ?? ucfirst($tDomain),
                'monthly_salary'   => $expected,
                'paid_amount'      => $paidAmount,
                'remaining_amount' => $remaining,
                'paid_percentage'  => $paidPercentage,
                'status'           => $status,
                'status_label'     => $statusLabel,
                'status_badge'     => $statusBadge,
                'record'           => $record,
            ]);
        }

        return $rows;
    }

    /**
     * Regroupe les versements réellement effectués par mois (accordéons mensuels).
     *
     * @return Collection Collection ordonnée par mois décroissant
     */
    public function getMonthlyPaymentGroups(?string $domain = null, string $schoolYear = '2026/2027', ?int $centerId = null): Collection
    {
        $paymentsQuery = Payment::with(['student.user', 'formation.center', 'center', 'recorder'])
            ->where('status', 'paid')
            ->orderBy('payment_date', 'desc');

        if ($schoolYear !== 'all') {
            $paymentsQuery->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)
                  ->orWhereNull('school_year');
            });
        }

        if ($centerId) {
            $paymentsQuery->where('center_id', $centerId);
        }

        if ($domain && $domain !== 'all') {
            $paymentsQuery->where(function ($q) use ($domain) {
                $q->where('domain', $domain)
                  ->orWhereHas('formation', fn($f) => $f->where('domain', $domain));
            });
        }

        $payments = $paymentsQuery->get();

        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return $payments->groupBy(function ($payment) {
            return $payment->payment_date ? $payment->payment_date->format('Y-m') : 'sans-date';
        })->map(function (Collection $group, string $key) use ($monthNames) {
            $totalCollected = (float) $group->sum('amount');
            $distinctStudents = $group->pluck('student_id')->unique()->count();

            $monthLabel = $key;
            $monthNum = 0;
            $yearNum = 0;
            if ($key !== 'sans-date' && preg_match('/^(\d{4})-(\d{2})$/', $key, $matches)) {
                $yearNum = (int) $matches[1];
                $monthNum = (int) $matches[2];
                $monthLabel = ($monthNames[$monthNum] ?? $matches[2]) . ' ' . $yearNum;
            }

            return (object) [
                'period_key'        => $key,
                'period_label'      => $monthLabel,
                'month_num'         => $monthNum,
                'year_num'          => $yearNum,
                'payments_count'    => $group->count(),
                'students_count'    => $distinctStudents,
                'total_collected'   => $totalCollected,
                'payments'          => $group,
            ];
        });
    }

    /**
     * Calcule l'évolution mensuelle consolidée (encaissements vs salaires versés) pour les graphiques.
     */
    public function getMonthlyEvolution(?string $domain = null, string $schoolYear = '2026/2027', ?int $centerId = null): array
    {
        $months = [
            ['month' => 9,  'year' => 2026, 'label' => 'Sep 26'],
            ['month' => 10, 'year' => 2026, 'label' => 'Oct 26'],
            ['month' => 11, 'year' => 2026, 'label' => 'Nov 26'],
            ['month' => 12, 'year' => 2026, 'label' => 'Déc 26'],
            ['month' => 1,  'year' => 2027, 'label' => 'Jan 27'],
            ['month' => 2,  'year' => 2027, 'label' => 'Fév 27'],
            ['month' => 3,  'year' => 2027, 'label' => 'Mar 27'],
            ['month' => 4,  'year' => 2027, 'label' => 'Avr 27'],
            ['month' => 5,  'year' => 2027, 'label' => 'Mai 27'],
            ['month' => 6,  'year' => 2027, 'label' => 'Juin 27'],
        ];

        $paymentsQuery = Payment::where('status', 'paid');
        if ($schoolYear !== 'all') {
            $paymentsQuery->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)->orWhereNull('school_year');
            });
        }
        if ($centerId) {
            $paymentsQuery->where('center_id', $centerId);
        }
        if ($domain && $domain !== 'all') {
            $paymentsQuery->where(function ($q) use ($domain) {
                $q->where('domain', $domain)->orWhereHas('formation', fn($f) => $f->where('domain', $domain));
            });
        }
        $payments = $paymentsQuery->get();

        $salariesQuery = TeacherSalary::where('status', 'paid');
        if ($schoolYear !== 'all') {
            $salariesQuery->where('school_year', $schoolYear);
        }
        if ($centerId) {
            $salariesQuery->where('center_id', $centerId);
        }
        if ($domain && $domain !== 'all') {
            $salariesQuery->where('domain', $domain);
        }
        $salaries = $salariesQuery->get();

        $items = [];
        $maxAmount = 5000.0;

        foreach ($months as $m) {
            $collected = (float) $payments->filter(function ($p) use ($m) {
                return $p->payment_date
                    && (int) $p->payment_date->format('n') === $m['month']
                    && (int) $p->payment_date->format('Y') === $m['year'];
            })->sum('amount');

            $salaryPaid = (float) $salaries->filter(function ($s) use ($m) {
                return (int) $s->month === $m['month'] && (int) $s->year === $m['year'];
            })->sum('paid_amount');

            if ($collected > $maxAmount) {
                $maxAmount = $collected;
            }
            if ($salaryPaid > $maxAmount) {
                $maxAmount = $salaryPaid;
            }

            $items[] = [
                'label'      => $m['label'],
                'full_month' => $m['label'] . ' ' . $m['year'],
                'month'      => $m['month'],
                'year'       => $m['year'],
                'collected'  => $collected,
                'salaries'   => $salaryPaid,
                'net'        => $collected - $salaryPaid,
            ];
        }

        return [
            'items'      => $items,
            'max_amount' => $maxAmount,
        ];
    }

    /**
     * Répartition des montants attendus et encaissés par domaine.
     */
    public function getDomainBreakdown(string $schoolYear = '2026/2027', ?int $centerId = null): Collection
    {
        $breakdown = collect();
        $domains = self::getDomains();

        foreach ($domains as $slug => $label) {
            $rows = $this->getStudentFinancialRows($slug, $schoolYear, $centerId);
            $expected = (float) $rows->sum('annual_price');
            $collected = (float) $rows->sum('paid_amount');
            $remaining = (float) $rows->sum('remaining_amount');
            $rate = $expected > 0 ? round(($collected / $expected) * 100, 1) : 0.0;

            $breakdown->put($slug, (object) [
                'slug'       => $slug,
                'label'      => $label,
                'students'   => $rows->count(),
                'expected'   => $expected,
                'collected'  => $collected,
                'remaining'  => $remaining,
                'rate'       => $rate,
            ]);
        }

        return $breakdown;
    }

    /**
     * Calcule l'ensemble des KPIs financiers globaux ou filtrés.
     */
    public function getOverviewKpis(?string $domain = null, string $schoolYear = '2026/2027', int $month = 10, int $year = 2026, ?int $centerId = null): array
    {
        $studentRows = $this->getStudentFinancialRows($domain, $schoolYear, $centerId);
        $teacherRows = $this->getTeacherSalaryRows($domain, $month, $year, $schoolYear, $centerId);

        $totalEncaise   = (float) $studentRows->sum('paid_amount');
        $totalAttendu   = (float) $studentRows->sum('annual_price');
        $totalRestantEtudiants = (float) $studentRows->sum('remaining_amount');

        $salairesPayes   = (float) $teacherRows->sum('paid_amount');
        $salairesRestant = (float) $teacherRows->sum('remaining_amount');
        $salairesAttendus = (float) $teacherRows->sum('monthly_salary');

        // Solde net de trésorerie (Paiements étudiants encaissés - Salaires professeurs payés)
        $soldeNet = $totalEncaise - $salairesPayes;

        // Taux de recouvrement des paiements étudiants
        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalEncaise / $totalAttendu) * 100, 1) : 0.0;
        $tauxSalaires     = $salairesAttendus > 0 ? round(($salairesPayes / $salairesAttendus) * 100, 1) : 0.0;

        return [
            'total_encaisse'          => $totalEncaise,
            'total_restant_etudiants' => $totalRestantEtudiants,
            'total_attendu'           => $totalAttendu,
            'salaires_payes'          => $salairesPayes,
            'salaires_restant'        => $salairesRestant,
            'salaires_attendus'       => $salairesAttendus,
            'solde_net'               => $soldeNet,
            'total_etudiants'         => $studentRows->count(),
            'total_professeurs'       => $teacherRows->count(),
            'taux_recouvrement'       => $tauxRecouvrement,
            'taux_salaires'           => $tauxSalaires,
            'etudiants_payes'         => $studentRows->where('status', 'paid')->count(),
            'etudiants_partiels'      => $studentRows->where('status', 'partial')->count(),
            'etudiants_impayes'       => $studentRows->where('status', 'unpaid')->count(),
            'professeurs_payes'       => $teacherRows->where('status', 'paid')->count(),
            'professeurs_partiels'    => $teacherRows->where('status', 'partial')->count(),
            'professeurs_impayes'     => $teacherRows->where('status', 'unpaid')->count(),
            'monthly_evolution'       => $this->getMonthlyEvolution($domain, $schoolYear, $centerId),
            'domain_breakdown'        => $this->getDomainBreakdown($schoolYear, $centerId),
        ];
    }
}
