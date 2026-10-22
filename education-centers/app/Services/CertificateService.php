<?php

namespace App\Services;

use App\Models\Center;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    public function __construct(private readonly PdfService $pdf) {}

    public function generate(Certificate $certificate): Certificate
    {
        $certificate->load(['student.user', 'center', 'enrollment.formation', 'issuer']);
        $viewMap = [
            'enrollment' => 'certificates.pdf.enrollment',
            'completion' => 'certificates.pdf.completion',
            'attendance' => 'certificates.pdf.attendance',
            'grades_report' => 'certificates.pdf.grades_report',
            'receipt' => 'certificates.pdf.receipt',
            'other' => 'certificates.pdf.other',
        ];
        $view = $viewMap[$certificate->type] ?? 'certificates.pdf.other';
        // Fallback: all types use a generic template if specific not found
        if (! view()->exists($view)) {
            $view = 'certificates.pdf.generic';
        }

        $content = $this->pdf->render($view, ['certificate' => $certificate]);
        $locale = app()->getLocale();
        $dir = "certificates/{$certificate->center_id}/{$certificate->student_id}";
        $file = $dir.'/'.$certificate->certificate_number.'-'.$locale.'.pdf';
        Storage::disk('private')->put($file, $content);
        $certificate->update(['file_path' => $file, 'status' => 'issued']);

        return $certificate;
    }

    public function createAndGenerate(
        Student $student,
        string $type,
        ?Enrollment $enrollment = null,
        ?User $issuedBy = null
    ): Certificate {
        $center = Center::findOrFail($student->center_id);
        $prefix = match ($type) {
            'enrollment' => 'ATT',
            'completion' => 'CERT',
            'attendance' => 'PRES',
            'grades_report' => 'NOTES',
            'receipt' => 'RECU',
            default => 'DOC',
        };
        $titleMap = [
            'enrollment' => __('Attestation d’inscription'),
            'completion' => __('Certificat de formation'),
            'attendance' => __('Attestation de présence'),
            'grades_report' => __('Relevé de notes'),
            'receipt' => __('Reçu de paiement'),
            'other' => __('Document'),
        ];
        $certificate = Certificate::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment?->id,
            'formation_id' => $enrollment?->formation_id,
            'certificate_number' => Certificate::generateCertificateNumber($center, $prefix),
            'type' => $type,
            'title' => $titleMap[$type] ?? __('Document'),
            'issued_date' => now()->toDateString(),
            'verify_token' => Certificate::generateVerifyToken(),
            'status' => 'draft',
            'issued_by' => $issuedBy?->id ?? auth()->id(),
        ]);

        return $this->generate($certificate);
    }
}
