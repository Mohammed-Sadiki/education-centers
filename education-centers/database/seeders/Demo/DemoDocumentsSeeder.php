<?php

namespace Database\Seeders\Demo;

use App\Models\Attendance;
use App\Models\Center;
use App\Models\Certificate;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\CertificateService;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDocumentsSeeder extends Seeder
{
    use DemoSeederSupport;

    public function run(): void
    {
        $certificateService = app(CertificateService::class);
        $pdfService = app(PdfService::class);
        $previousLocale = app()->getLocale();
        app()->setLocale('fr');

        try {
            foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
                $recorderId = $this->centerAdminId($center);
                $this->seedCertificates($center, $recorderId, $certificateService);
                $this->seedAdministrativeDocuments($center, $recorderId, $pdfService);
            }
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    private function seedCertificates(Center $center, int $recorderId, CertificateService $service): void
    {
        $domains = DemoFixtures::domainSlugs();
        $sequence = 1;

        foreach ($domains as $domainIndex => $domain) {
            $students = $this->acceptedStudentsForDomain($center, $domain);

            $completionStudent = $students->first(fn (Student $student) => $this->isCompletionEligible($center, $student));
            if ($completionStudent) {
                $enrollment = $this->acceptedEnrollment($center, $completionStudent);
                $this->createCertificate(
                    $center,
                    $completionStudent,
                    $enrollment,
                    'completion',
                    $sequence++,
                    $recorderId,
                    $service,
                );
            }

            if ($domainIndex >= 3) {
                continue;
            }

            $gradesStudent = $students->first(fn (Student $student) => Grade::query()
                ->where('center_id', $center->id)
                ->where('student_id', $student->id)
                ->whereNotNull('grade')
                ->exists());

            if ($gradesStudent) {
                $this->createCertificate(
                    $center,
                    $gradesStudent,
                    $this->acceptedEnrollment($center, $gradesStudent),
                    'grades_report',
                    $sequence++,
                    $recorderId,
                    $service,
                );
            }

            $attendanceStudent = $students->first(fn (Student $student) => Attendance::query()
                ->where('center_id', $center->id)
                ->where('student_id', $student->id)
                ->exists());

            if ($attendanceStudent) {
                $this->createCertificate(
                    $center,
                    $attendanceStudent,
                    $this->acceptedEnrollment($center, $attendanceStudent),
                    'attendance',
                    $sequence++,
                    $recorderId,
                    $service,
                );
            }

            $receiptStudent = $students->first(fn (Student $student) => Payment::query()
                ->where('center_id', $center->id)
                ->where('student_id', $student->id)
                ->where('status', 'paid')
                ->exists());

            if ($receiptStudent) {
                $this->createCertificate(
                    $center,
                    $receiptStudent,
                    $this->acceptedEnrollment($center, $receiptStudent),
                    'receipt',
                    $sequence++,
                    $recorderId,
                    $service,
                );
            }

            $enrollmentStudent = $students->first();
            if ($enrollmentStudent) {
                $this->createCertificate(
                    $center,
                    $enrollmentStudent,
                    $this->acceptedEnrollment($center, $enrollmentStudent),
                    'enrollment',
                    $sequence++,
                    $recorderId,
                    $service,
                );
            }
        }
    }

    private function createCertificate(
        Center $center,
        Student $student,
        ?Enrollment $enrollment,
        string $type,
        int $sequence,
        int $recorderId,
        CertificateService $service,
    ): void {
        if (! $enrollment) {
            return;
        }

        $prefix = match ($type) {
            'enrollment' => 'ATT',
            'completion' => 'CERT',
            'attendance' => 'PRES',
            'grades_report' => 'NOTES',
            'receipt' => 'RECU',
            default => 'DOC',
        };

        $cc = DemoFixtures::centerCode($center);
        $number = sprintf('%s-%s-DEMO-%03d', $prefix, $cc, $sequence);

        $title = match ($type) {
            'enrollment' => 'Attestation d’inscription',
            'completion' => 'Certificat de formation',
            'attendance' => 'Attestation de présence',
            'grades_report' => 'Relevé de notes',
            'receipt' => 'Reçu de paiement',
            default => 'Document administratif',
        };

        $certificate = Certificate::query()->updateOrCreate(
            ['certificate_number' => $number],
            [
                'center_id' => $center->id,
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'formation_id' => $enrollment->formation_id,
                'type' => $type,
                'title' => $title,
                'issued_date' => '2026-10-20',
                'valid_until' => in_array($type, ['completion', 'enrollment'], true) ? '2027-06-30' : null,
                'verify_token' => hash('sha256', 'demo-certificate-'.$number),
                'status' => 'issued',
                'issued_by' => $recorderId,
            ],
        );

        $service->generate($certificate);
    }

    private function seedAdministrativeDocuments(Center $center, int $recorderId, PdfService $pdf): void
    {
        $students = Enrollment::query()
            ->where('center_id', $center->id)
            ->where('status', Enrollment::STATUS_ACCEPTED)
            ->with(['student', 'formation', 'classRoom'])
            ->orderBy('student_id')
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->take(2)
            ->values();

        $templates = [
            [
                'type' => 'administrative',
                'title' => 'Certificat de scolarité 2026/2027',
                'slug' => 'certificat-scolarite',
                'body' => 'Le centre atteste que l’étudiant(e) est régulièrement inscrit(e) pour l’année scolaire 2026/2027.',
            ],
            [
                'type' => 'attestation',
                'title' => 'Attestation de fréquentation',
                'slug' => 'attestation-frequentation',
                'body' => 'Le centre atteste de la participation de l’étudiant(e) aux séances organisées dans le cadre de sa formation.',
            ],
            [
                'type' => 'other',
                'title' => 'Dossier administratif de démonstration',
                'slug' => 'dossier-administratif',
                'body' => 'Récapitulatif administratif fictif : inscription, formation, classe, facturation et paiements enregistrés.',
            ],
        ];

        foreach ($students as $student) {
            $enrollment = $this->acceptedEnrollment($center, $student);

            if (! $enrollment) {
                continue;
            }

            $formation = $enrollment->formation;
            $class = $enrollment->classRoom;
            $invoice = Invoice::query()
                ->where('center_id', $center->id)
                ->where('student_id', $student->id)
                ->latest('id')
                ->first();
            $paid = (float) Payment::query()
                ->where('center_id', $center->id)
                ->where('student_id', $student->id)
                ->where('status', 'paid')
                ->sum('amount');
            $totalInvoiced = (float) ($invoice?->total_amount ?? 0);
            $remaining = max(0, $totalInvoiced - $paid);

            foreach ($templates as $template) {
                $extra = '';
                if ($template['type'] === 'other') {
                    $extra = sprintf(
                        '<p><strong>Facture :</strong> %s — <strong>Total :</strong> %s — <strong>Payé :</strong> %s — <strong>Solde :</strong> %s</p>',
                        e($invoice?->invoice_number ?? 'Aucune facture'),
                        e(format_price($totalInvoiced, default_currency())),
                        e(format_price($paid, default_currency())),
                        e(format_price($remaining, default_currency())),
                    );
                }

                $html = $this->documentHtml(
                    $center,
                    $student,
                    $enrollment,
                    $template['title'],
                    $template['body'].$extra,
                );

                $file = sprintf(
                    'documents/demo/%d/%d/%s.pdf',
                    $center->id,
                    $student->id,
                    $template['slug'],
                );
                $bytes = $pdf->renderHtml($html, 'A4', 'portrait', $template['title']);
                Storage::disk('private')->put($file, $bytes);

                Document::query()->updateOrCreate(
                    [
                        'center_id' => $center->id,
                        'student_id' => $student->id,
                        'type' => $template['type'],
                        'title' => $template['title'],
                    ],
                    [
                        'description' => $template['body'],
                        'file_path' => $file,
                        'original_name' => $template['slug'].'.pdf',
                        'mime_type' => 'application/pdf',
                        'size' => strlen($bytes),
                        'uploaded_by' => $recorderId,
                        'status' => 'active',
                    ],
                );
            }
        }
    }

    private function documentHtml(
        Center $center,
        Student $student,
        Enrollment $enrollment,
        string $title,
        string $body,
    ): string {
        $formation = e($enrollment->formation?->name ?? 'Formation');
        $class = e($enrollment->classRoom?->name ?? 'Classe non affectée');
        $studentName = e($student->full_name);
        $studentNumber = e($student->student_number);
        $centerName = e($center->name);
        $centerAddress = e(trim(($center->address ?? '').' '.($center->postal_code ?? '').' '.($center->city ?? '')));
        $reference = e('DOC-'.DemoFixtures::centerCode($center).'-'.$student->id.'-'.Str::slug($title));
        $date = Carbon::parse('2026-10-20')->format('d/m/Y');

        return '<!DOCTYPE html><html lang="fr" dir="ltr"><head><meta charset="utf-8"><title>'.e($title).'</title>'
            .'<style>body{font-family:dejavusans,sans-serif;color:#0f172a;font-size:12px;line-height:1.65;margin:0}'
            .'.frame{border:2px solid #0DAD98;padding:28px;min-height:250mm}.head{width:100%;border-collapse:collapse;margin-bottom:24px}'
            .'.center{font-size:19px;font-weight:bold;color:#0DAD98}.muted{color:#64748b;font-size:10px}'
            .'h1{text-align:center;font-size:24px;margin:26px 0 8px}.ref{text-align:right;font-size:10px;color:#64748b}'
            .'table.grid{width:100%;border-collapse:collapse;margin-top:22px}table.grid th,table.grid td{border:1px solid #cbd5e1;padding:8px;text-align:left}'
            .'table.grid th{background:#f1f5f9}.notice{margin-top:26px;padding:12px;background:#f8fafc;border-left:4px solid #0DAD98}'
            .'footer{margin-top:44px;font-size:10px;color:#64748b;text-align:center}</style></head><body><div class="frame">'
            .'<table class="head"><tr><td><div class="center">'.$centerName.'</div><div class="muted">'.$centerAddress.'</div></td>'
            .'<td class="ref">Référence : '.$reference.'<br>Émis le '.$date.'</td></tr></table>'
            .'<h1>'.e($title).'</h1><p>'.$body.'</p>'
            .'<table class="grid"><tr><th>Étudiant(e)</th><td>'.$studentName.'</td></tr>'
            .'<tr><th>Numéro étudiant</th><td>'.$studentNumber.'</td></tr>'
            .'<tr><th>Formation</th><td>'.$formation.'</td></tr>'
            .'<tr><th>Classe</th><td>'.$class.'</td></tr>'
            .'<tr><th>Année scolaire</th><td>'.e($enrollment->school_year).'</td></tr>'
            .'<tr><th>Statut de l’inscription</th><td>'.e($enrollment->status_label).'</td></tr></table>'
            .'<div class="notice">Ce document est une donnée de démonstration entièrement fictive. '
            .'Il ne constitue pas une pièce administrative officielle.</div>'
            .'<footer>'.$centerName.' — Document généré depuis les données réelles de la base de démonstration.</footer>'
            .'</div></body></html>';
    }

    private function acceptedStudentsForDomain(Center $center, string $domain)
    {
        return Student::query()
            ->where('center_id', $center->id)
            ->whereHas('enrollments', fn ($query) => $query
                ->where('status', Enrollment::STATUS_ACCEPTED)
                ->whereHas('formation', fn ($formation) => $formation->where('domain', $domain)))
            ->orderBy('student_number')
            ->get();
    }

    private function acceptedEnrollment(Center $center, Student $student): ?Enrollment
    {
        return Enrollment::query()
            ->where('center_id', $center->id)
            ->where('student_id', $student->id)
            ->where('status', Enrollment::STATUS_ACCEPTED)
            ->with(['formation', 'classRoom'])
            ->orderByDesc('id')
            ->first();
    }

    private function isCompletionEligible(Center $center, Student $student): bool
    {
        $enrollment = $this->acceptedEnrollment($center, $student);

        if (! $enrollment) {
            return false;
        }

        $hasPaidInvoice = Invoice::query()
            ->where('center_id', $center->id)
            ->where('student_id', $student->id)
            ->where('status', 'paid')
            ->get()
            ->contains(fn (Invoice $invoice) => $invoice->remaining_amount <= 0.01);

        if (! $hasPaidInvoice) {
            return false;
        }

        $attendanceQuery = Attendance::query()
            ->where('center_id', $center->id)
            ->where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id);

        $attendanceTotal = (clone $attendanceQuery)->count();
        $attendancePresent = (clone $attendanceQuery)->whereIn('status', ['present', 'late'])->count();
        $attendanceRate = $attendanceTotal > 0 ? $attendancePresent / $attendanceTotal : 0;

        $average = Grade::query()
            ->where('center_id', $center->id)
            ->where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id)
            ->whereNotNull('grade')
            ->avg('grade');

        return $attendanceRate >= 0.8 && ($average !== null && $average >= 10);
    }
}
