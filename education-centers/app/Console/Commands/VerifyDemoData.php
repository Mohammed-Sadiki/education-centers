<?php

namespace App\Console\Commands;

use App\Models\Center;
use App\Models\Certificate;
use App\Models\ClassRoom;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use Database\Seeders\Demo\DemoFixtures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerifyDemoData extends Command
{
    protected $signature = 'demo:verify
        {--json : Affiche le rapport complet au format JSON}
        {--strict : Retourne un code d’erreur si les volumes minimaux ne sont pas atteints}';

    protected $description = 'Vérifie les volumes, l’isolation Berlin/Munich, les calculs financiers et les PDF de démonstration';

    public function handle(): int
    {
        $centers = Center::query()
            ->whereIn('code', ['BERLIN', 'MUNICH'])
            ->orderBy('id')
            ->get();

        if ($centers->count() !== 2) {
            $this->error('Les deux centres BERLIN et MUNICH sont requis pour vérifier la démonstration.');

            return self::FAILURE;
        }

        $errors = [];
        $report = ['centers' => [], 'errors' => [], 'ok' => true];

        foreach ($centers as $center) {
            $counts = $this->counts($center);
            $domains = $this->domainCounts($center);
            $finance = $this->financeStats($center);
            $errors = array_merge($errors, $this->consistencyErrors($center));
            $errors = array_merge($errors, $this->pdfErrors($center));

            if ($this->option('strict')) {
                $minimums = [
                    'formations' => 12,
                    'students' => 60,
                    'teachers' => 12,
                    'classes' => 12,
                    'enrollments' => 60,
                    'schedules' => 36,
                    'evaluations' => 24,
                    'invoices' => 40,
                    'payments' => 50,
                    'expenses' => 15,
                    'teacher_salaries' => 12,
                ];

                foreach ($minimums as $metric => $minimum) {
                    if (($counts[$metric] ?? 0) < $minimum) {
                        $errors[] = sprintf(
                            '%s : %s insuffisant(s) (%d < %d).',
                            $center->code,
                            $metric,
                            $counts[$metric] ?? 0,
                            $minimum,
                        );
                    }
                }
            }

            $report['centers'][$center->code] = [
                'counts' => $counts,
                'domains' => $domains,
                'finance' => $finance,
            ];
        }

        $report['errors'] = $errors;
        $report['ok'] = $errors === [];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderReport($centers, $report);
        }

        if ($errors !== []) {
            $this->newLine();
            $this->error(sprintf('%d anomalie(s) détectée(s).', count($errors)));

            foreach (array_slice($errors, 0, 20) as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Vérification DEMO réussie : volumes, isolation, finances et PDF cohérents.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    private function counts(Center $center): array
    {
        return [
            'formations' => Formation::query()->where('center_id', $center->id)->count(),
            'students' => Student::query()->where('center_id', $center->id)->count(),
            'teachers' => Teacher::query()->where('center_id', $center->id)->count(),
            'classes' => ClassRoom::query()->where('center_id', $center->id)->count(),
            'enrollments' => Enrollment::query()->where('center_id', $center->id)->count(),
            'schedules' => DB::table('schedules')->where('center_id', $center->id)->count(),
            'attendances' => DB::table('attendances')->where('center_id', $center->id)->count(),
            'evaluations' => DB::table('evaluations')->where('center_id', $center->id)->count(),
            'grades' => DB::table('grades')->where('center_id', $center->id)->count(),
            'invoices' => Invoice::query()->where('center_id', $center->id)->count(),
            'payments' => Payment::query()->where('center_id', $center->id)->count(),
            'expenses' => DB::table('expenses')->where('center_id', $center->id)->count(),
            'teacher_salaries' => DB::table('teacher_salaries')->where('center_id', $center->id)->count(),
            'certificates' => Certificate::query()->where('center_id', $center->id)->count(),
            'documents' => Document::query()->where('center_id', $center->id)->count(),
            'bank_transactions' => DB::table('bank_transactions')->where('center_id', $center->id)->count(),
            'accounting_entries' => DB::table('accounting_entries')->where('center_id', $center->id)->count(),
            'rooms' => DB::table('rooms')->where('center_id', $center->id)->count(),
        ];
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function domainCounts(Center $center): array
    {
        $result = [];

        foreach (DemoFixtures::domainSlugs() as $domain) {
            $result[$domain] = [
                'formations' => Formation::query()->where('center_id', $center->id)->forDomain($domain)->count(),
                'classes' => ClassRoom::query()->where('center_id', $center->id)->forDomain($domain)->count(),
                'students' => Student::query()->where('center_id', $center->id)->forDomain($domain)->count(),
                'teachers' => Teacher::query()->where('center_id', $center->id)->forDomain($domain)->count(),
                'enrollments' => Enrollment::query()->where('center_id', $center->id)->forDomain($domain)->count(),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function financeStats(Center $center): array
    {
        $invoices = Invoice::query()->where('center_id', $center->id)->get();
        $paidInvoices = 0;
        $partialInvoices = 0;
        $unpaidInvoices = 0;
        $overdueInvoices = 0;
        $totalInvoiced = 0.0;
        $totalPaidOnInvoices = 0.0;
        $negativeRemaining = 0;

        foreach ($invoices as $invoice) {
            $total = (float) $invoice->total_amount;
            $paid = (float) $invoice->total_paid;
            $totalInvoiced += $total;
            $totalPaidOnInvoices += $paid;

            if ($paid > $total + 0.01) {
                $negativeRemaining++;
            }

            if ($paid >= $total - 0.01) {
                $paidInvoices++;
            } elseif ($paid > 0) {
                $partialInvoices++;
            } else {
                $unpaidInvoices++;
            }

            if ($invoice->status === 'overdue') {
                $overdueInvoices++;
            }
        }

        $payments = Payment::query()->where('center_id', $center->id)->get();

        return [
            'invoices_total' => $invoices->count(),
            'invoices_paid' => $paidInvoices,
            'invoices_partial' => $partialInvoices,
            'invoices_unpaid' => $unpaidInvoices,
            'invoices_overdue' => $overdueInvoices,
            'invoiced_total' => round($totalInvoiced, 2),
            'invoiced_paid' => round($totalPaidOnInvoices, 2),
            'invoiced_remaining' => round(max(0, $totalInvoiced - $totalPaidOnInvoices), 2),
            'invoices_with_overpayment' => $negativeRemaining,
            'payments_total' => $payments->count(),
            'payments_paid' => $payments->where('status', 'paid')->count(),
            'payments_pending' => $payments->where('status', 'pending')->count(),
            'payments_amount_paid' => round((float) $payments->where('status', 'paid')->sum('amount'), 2),
        ];
    }

    /**
     * @return list<string>
     */
    private function consistencyErrors(Center $center): array
    {
        $errors = [];
        $queries = [
            'classes' => DB::table('classes as c')
                ->join('formations as f', 'f.id', '=', 'c.formation_id')
                ->where('c.center_id', $center->id)
                ->whereRaw('c.center_id <> f.center_id'),
            'inscriptions' => DB::table('enrollments as e')
                ->join('students as s', 's.id', '=', 'e.student_id')
                ->join('formations as f', 'f.id', '=', 'e.formation_id')
                ->leftJoin('classes as c', 'c.id', '=', 'e.class_id')
                ->where('e.center_id', $center->id)
                ->whereRaw('e.center_id <> s.center_id OR e.center_id <> f.center_id OR (e.class_id IS NOT NULL AND e.center_id <> c.center_id)'),
            'paiements' => DB::table('payments as p')
                ->join('students as s', 's.id', '=', 'p.student_id')
                ->leftJoin('formations as f', 'f.id', '=', 'p.formation_id')
                ->leftJoin('invoices as i', 'i.id', '=', 'p.invoice_id')
                ->leftJoin('enrollments as e', 'e.id', '=', 'p.enrollment_id')
                ->where('p.center_id', $center->id)
                ->whereRaw('p.center_id <> s.center_id OR (p.formation_id IS NOT NULL AND p.center_id <> f.center_id) OR (p.invoice_id IS NOT NULL AND p.center_id <> i.center_id) OR (p.enrollment_id IS NOT NULL AND p.center_id <> e.center_id)'),
            'seances' => DB::table('schedules as sc')
                ->join('classes as c', 'c.id', '=', 'sc.class_id')
                ->join('teachers as t', 't.id', '=', 'sc.teacher_id')
                ->join('rooms as r', 'r.id', '=', 'sc.room_id')
                ->where('sc.center_id', $center->id)
                ->whereRaw('sc.center_id <> c.center_id OR sc.center_id <> t.center_id OR sc.center_id <> r.center_id'),
            'notes' => DB::table('grades as g')
                ->join('evaluations as ev', 'ev.id', '=', 'g.evaluation_id')
                ->join('students as s', 's.id', '=', 'g.student_id')
                ->where('g.center_id', $center->id)
                ->whereRaw('g.center_id <> ev.center_id OR g.center_id <> s.center_id'),
            'presences' => DB::table('attendances as a')
                ->join('schedules as sc', 'sc.id', '=', 'a.schedule_id')
                ->join('classes as c', 'c.id', '=', 'a.class_id')
                ->join('students as s', 's.id', '=', 'a.student_id')
                ->where('a.center_id', $center->id)
                ->whereRaw('a.center_id <> sc.center_id OR a.center_id <> c.center_id OR a.center_id <> s.center_id'),
            'documents' => DB::table('documents as d')
                ->join('students as s', 's.id', '=', 'd.student_id')
                ->where('d.center_id', $center->id)
                ->whereRaw('d.center_id <> s.center_id'),
            'certificats' => DB::table('certificates as ce')
                ->join('students as s', 's.id', '=', 'ce.student_id')
                ->where('ce.center_id', $center->id)
                ->whereRaw('ce.center_id <> s.center_id'),
            'affectations_professeurs' => DB::table('class_teacher as ct')
                ->join('classes as c', 'c.id', '=', 'ct.class_id')
                ->join('teachers as t', 't.id', '=', 'ct.teacher_id')
                ->where('c.center_id', $center->id)
                ->whereRaw('c.center_id <> t.center_id'),
            'salaires' => DB::table('teacher_salaries as ts')
                ->join('teachers as t', 't.id', '=', 'ts.teacher_id')
                ->where('ts.center_id', $center->id)
                ->whereRaw('ts.center_id <> t.center_id'),
            'planning' => DB::table('schedules as s1')
                ->join('schedules as s2', function ($join): void {
                    $join->on('s1.center_id', '=', 's2.center_id')
                        ->whereColumn('s1.id', '<', 's2.id')
                        ->whereColumn('s1.date', '=', 's2.date')
                        ->whereColumn('s1.start_time', '<', 's2.end_time')
                        ->whereColumn('s2.start_time', '<', 's1.end_time');
                })
                ->where('s1.center_id', $center->id)
                ->where(function ($query): void {
                    $query->whereColumn('s1.teacher_id', '=', 's2.teacher_id')
                        ->orWhereColumn('s1.room_id', '=', 's2.room_id')
                        ->orWhereColumn('s1.class_id', '=', 's2.class_id');
                }),
        ];

        $classesWithoutTeacher = DB::table('classes as c')
            ->leftJoin('class_teacher as ct', 'ct.class_id', '=', 'c.id')
            ->where('c.center_id', $center->id)
            ->whereNull('ct.id')
            ->count();

        if ($classesWithoutTeacher > 0) {
            $errors[] = sprintf('%s : %d classe(s) sans professeur affecté.', $center->code, $classesWithoutTeacher);
        }

        foreach ($queries as $label => $query) {
            $count = $query->count();

            if ($count > 0) {
                $errors[] = sprintf('%s : %d relation(s) %s traversent plusieurs centres.', $center->code, $count, $label);
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function pdfErrors(Center $center): array
    {
        $errors = [];

        $certificates = Certificate::query()
            ->where('center_id', $center->id)
            ->where('certificate_number', 'like', '%-DEMO-%')
            ->get();

        foreach ($certificates as $certificate) {
            if (! $certificate->file_path || ! Storage::disk('private')->exists($certificate->file_path)) {
                $errors[] = sprintf('%s : PDF du certificat %s absent.', $center->code, $certificate->certificate_number);
            }
        }

        $documents = Document::query()
            ->where('center_id', $center->id)
            ->where('file_path', 'like', 'documents/demo/%')
            ->get();

        foreach ($documents as $document) {
            if (! Storage::disk('private')->exists($document->file_path)) {
                $errors[] = sprintf('%s : PDF du document « %s » absent.', $center->code, $document->title);
            }
        }

        return $errors;
    }

    private function renderReport($centers, array $report): void
    {
        $metrics = [
            'formations' => 'Formations',
            'students' => 'Étudiants',
            'teachers' => 'Professeurs',
            'classes' => 'Classes',
            'enrollments' => 'Inscriptions',
            'schedules' => 'Séances',
            'attendances' => 'Présences',
            'evaluations' => 'Évaluations',
            'grades' => 'Notes',
            'invoices' => 'Factures',
            'payments' => 'Paiements',
            'expenses' => 'Dépenses',
            'teacher_salaries' => 'Salaires',
            'certificates' => 'Certificats',
            'documents' => 'Documents',
            'bank_transactions' => 'Transactions bancaires',
            'accounting_entries' => 'Écritures comptables',
        ];

        $headers = ['Indicateur'];
        foreach ($centers as $center) {
            $headers[] = $center->code;
        }

        $rows = [];
        foreach ($metrics as $key => $label) {
            $row = [$label];
            foreach ($centers as $center) {
                $row[] = $report['centers'][$center->code]['counts'][$key] ?? 0;
            }
            $rows[] = $row;
        }

        $this->components->info('Volumes réels par table et par centre');
        $this->table($headers, $rows);

        foreach ($centers as $center) {
            $this->components->info('Répartition par domaine — '.$center->code);
            $domainRows = [];
            foreach (DemoFixtures::domainSlugs() as $domain) {
                $values = $report['centers'][$center->code]['domains'][$domain];
                $domainRows[] = [
                    DemoFixtures::domainLabel($domain),
                    $values['formations'],
                    $values['classes'],
                    $values['students'],
                    $values['teachers'],
                    $values['enrollments'],
                ];
            }
            $this->table(['Domaine', 'Formations', 'Classes', 'Étudiants', 'Professeurs', 'Inscriptions'], $domainRows);
        }

        $financeRows = [];
        foreach ($centers as $center) {
            $finance = $report['centers'][$center->code]['finance'];
            $financeRows[] = [
                $center->code,
                $finance['invoices_total'],
                $finance['invoices_paid'],
                $finance['invoices_partial'],
                $finance['invoices_unpaid'],
                $finance['invoices_overdue'],
                format_price($finance['invoiced_total'], default_currency()),
                format_price($finance['invoiced_paid'], default_currency()),
                format_price($finance['invoiced_remaining'], default_currency()),
                $finance['payments_total'],
            ];
        }

        $this->components->info('Synthèse financière');
        $this->table(
            ['Centre', 'Factures', 'Payées', 'Partielles', 'Impayées', 'En retard', 'Facturé', 'Encaissé', 'Reste', 'Paiements'],
            $financeRows,
        );
    }
}
