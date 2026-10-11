<?php
namespace App\Services;
use App\Models\Certificate;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class CertificateService {
    public function generate(Certificate $certificate): Certificate {
        $certificate->load(['student.user','center','enrollment.formation','issuer']);
        $viewMap = [
            'enrollment'   => 'certificates.pdf.enrollment',
            'completion'   => 'certificates.pdf.completion',
            'attendance'   => 'certificates.pdf.attendance',
            'grades_report'=> 'certificates.pdf.grades_report',
            'receipt'      => 'certificates.pdf.receipt',
            'other'        => 'certificates.pdf.other',
        ];
        $view = $viewMap[$certificate->type] ?? 'certificates.pdf.other';
        // Fallback: all types use a generic template if specific not found
        if (!view()->exists($view)) $view = 'certificates.pdf.generic';
        $pdf = Pdf::loadView($view, ['certificate' => $certificate])
            ->setPaper('a4', 'portrait');
        $dir  = "certificates/{$certificate->center_id}/{$certificate->student_id}";
        $file = $dir . '/' . $certificate->certificate_number . '.pdf';
        Storage::disk('private')->put($file, $pdf->output());
        $certificate->update(['file_path' => $file, 'status' => 'issued']);
        return $certificate;
    }
    public function createAndGenerate(
        Student $student,
        string  $type,
        ?Enrollment $enrollment = null,
        User    $issuedBy = null
    ): Certificate {
        $center = Center::findOrFail($student->center_id);
        $prefix = match($type) {
            'enrollment'    => 'ATT',
            'completion'    => 'CERT',
            'attendance'    => 'PRES',
            'grades_report' => 'NOTES',
            'receipt'       => 'RECU',
            default         => 'DOC',
        };
        $titleMap = [
            'enrollment'    => "Attestation d'\''inscription",
            'completion'    => "Certificat de formation",
            'attendance'    => "Attestation de présence",
            'grades_report' => "Relevé de notes",
            'receipt'       => "Reçu de paiement",
            'other'         => "Document",
        ];
        $certificate = Certificate::create([
            'center_id'          => $student->center_id,
            'student_id'         => $student->id,
            'enrollment_id'      => $enrollment?->id,
            'formation_id'       => $enrollment?->formation_id,
            'certificate_number' => Certificate::generateCertificateNumber($center, $prefix),
            'type'               => $type,
            'title'              => $titleMap[$type] ?? 'Document',
            'issued_date'        => now()->toDateString(),
            'verify_token'       => Certificate::generateVerifyToken(),
            'status'             => 'draft',
            'issued_by'          => $issuedBy?->id ?? auth()->id(),
        ]);
        return $this->generate($certificate);
    }
}