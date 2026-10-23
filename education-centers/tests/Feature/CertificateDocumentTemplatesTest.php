<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Formation;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mission PDF — Certificats/Attestations : chaque type conserve son propre
 * modèle et ses propres données ; téléchargement vs impression identiques.
 */
class CertificateDocumentTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private User $superAdmin;

    private User $admin;

    private Student $student;

    private Formation $formation;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::create(['name' => 'Alpha', 'code' => 'AL', 'address' => 'A', 'status' => 'active']);
        $this->superAdmin = User::factory()->superAdmin()->create(['status' => 'active']);
        $this->admin = User::factory()->centerAdmin($this->center->id)->create(['status' => 'active']);

        $this->formation = Formation::create([
            'center_id' => $this->center->id, 'domain' => 'sante', 'name' => 'Soins infirmiers',
            'code' => 'SAN-01', 'status' => 'active',
        ]);

        $user = User::factory()->create(['role' => 'student', 'center_id' => $this->center->id]);
        $this->student = Student::create([
            'user_id' => $user->id, 'center_id' => $this->center->id, 'student_number' => 'AL-000001',
            'first_name' => 'Yasmine', 'last_name' => 'El Amrani', 'date_of_birth' => '2000-01-01',
            'gender' => 'female', 'status' => 'active',
        ]);

        $this->enrollment = Enrollment::create([
            'center_id' => $this->center->id, 'student_id' => $this->student->id, 'formation_id' => $this->formation->id,
            'registration_number' => 'E-1', 'application_date' => '2026-01-01', 'status' => 'accepted',
        ]);
    }

    /**
     * Build an unsaved certificate wired to its relations, for pure template tests.
     */
    private function certificate(string $type, string $number, string $title): Certificate
    {
        $certificate = new Certificate([
            'center_id' => $this->center->id,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'formation_id' => $this->formation->id,
            'certificate_number' => $number,
            'type' => $type,
            'title' => $title,
            'issued_date' => '2026-06-10',
            'verify_token' => 'TOKEN-'.$type,
            'status' => 'issued',
        ]);
        $certificate->setRelation('center', $this->center);
        $certificate->setRelation('student', $this->student);
        $certificate->setRelation('formation', $this->formation);

        return $certificate;
    }

    public function test_enrollment_attestation_uses_its_own_template(): void
    {
        $certificate = $this->certificate('enrollment', 'ATT-AL-000001', 'Attestation d’inscription');

        $html = view('certificates.pdf.enrollment', [
            'certificate' => $certificate,
            'center' => $this->center,
            'docNumber' => $certificate->certificate_number,
            'docDate' => format_date($certificate->issued_date, 'd M Y'),
        ])->render();

        $this->assertStringContainsString('Attestation administrative', $html);
        $this->assertStringContainsString('Yasmine', $html);
        $this->assertStringContainsString('Soins infirmiers', $html);
        // Not a receipt, not a grade report.
        $this->assertStringNotContainsString('Total encaissé', $html);
        $this->assertStringNotContainsString('Moyenne générale', $html);
    }

    public function test_grades_report_shows_real_grades_and_weighted_average(): void
    {
        $evaluation = new Evaluation([
            'title' => 'Examen final', 'type' => 'exam', 'max_grade' => 20,
            'coefficient' => 2, 'evaluation_date' => '2026-06-01', 'status' => 'published',
        ]);
        $grade = new Grade(['grade' => 14.5, 'is_absent' => false, 'graded_at' => '2026-06-01']);
        $grade->setRelation('evaluation', $evaluation);

        $certificate = $this->certificate('grades_report', 'NOTES-AL-000001', 'Relevé de notes');

        $html = view('certificates.pdf.grades_report', [
            'certificate' => $certificate,
            'center' => $this->center,
            'grades' => collect([$grade]),
        ])->render();

        $this->assertStringContainsString('Relevé officiel des notes', $html);
        $this->assertStringContainsString('Moyenne générale', $html);
        $this->assertStringContainsString('14,50', $html);
        $this->assertStringContainsString('Examen final', $html);
        $this->assertStringNotContainsString('Prestations facturées', $html);
    }

    public function test_grades_report_empty_state(): void
    {
        $certificate = $this->certificate('grades_report', 'NOTES-AL-000002', 'Relevé de notes');

        $html = view('certificates.pdf.grades_report', [
            'certificate' => $certificate,
            'center' => $this->center,
            'grades' => collect(),
        ])->render();

        $this->assertStringContainsString('Aucune note enregistrée', $html);
    }

    public function test_receipt_lists_only_paid_payments(): void
    {
        $paid = new Payment(['amount' => 5000, 'currency' => 'MAD', 'payment_date' => '2026-05-01', 'method' => 'cash', 'reference' => 'PAY-OK', 'status' => 'paid']);
        $pending = new Payment(['amount' => 1000, 'currency' => 'MAD', 'payment_date' => '2026-05-02', 'method' => 'cash', 'reference' => 'PAY-PEND', 'status' => 'pending']);

        $certificate = $this->certificate('receipt', 'RECU-AL-000001', 'Reçu de paiement');

        $html = view('certificates.pdf.receipt', [
            'certificate' => $certificate,
            'center' => $this->center,
            'payments' => collect([$paid, $pending]),
        ])->render();

        $this->assertStringContainsString('Attestation de paiement', $html);
        $this->assertStringContainsString('Total encaissé', $html);
        $this->assertStringContainsString('PAY-OK', $html);
        $this->assertStringNotContainsString('PAY-PEND', $html);
    }

    public function test_attendance_summary_computes_real_rate(): void
    {
        $rows = collect([
            new \App\Models\Attendance(['status' => 'present']),
            new \App\Models\Attendance(['status' => 'present']),
            new \App\Models\Attendance(['status' => 'absent']),
            new \App\Models\Attendance(['status' => 'late']),
        ]);

        $certificate = $this->certificate('attendance', 'PRES-AL-000001', 'Attestation de présence');

        $html = view('certificates.pdf.attendance', [
            'certificate' => $certificate,
            'center' => $this->center,
            'attendances' => $rows,
        ])->render();

        $this->assertStringContainsString('Récapitulatif de présence', $html);
        $this->assertStringContainsString('50 %', $html); // 2 presents / 4 séances
    }

    public function test_completion_certificate_still_generates_pdf_file(): void
    {
        $service = app(CertificateService::class);
        $certificate = $service->createAndGenerate($this->student, 'completion', $this->enrollment, $this->admin);

        $this->assertNotNull($certificate->file_path);
        $this->assertTrue(Storage::disk('private')->exists($certificate->file_path));
        $this->assertStringStartsWith('%PDF', Storage::disk('private')->get($certificate->file_path));
    }

    public function test_certificate_download_and_print_serve_the_same_pdf(): void
    {
        $service = app(CertificateService::class);
        $certificate = $service->createAndGenerate($this->student, 'completion', $this->enrollment, $this->admin);

        $stored = Storage::disk('private')->get($certificate->file_path);

        $print = $this->actingAs($this->superAdmin)
            ->get(route('certificates.print', $certificate))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString('inline;', $print->headers->get('Content-Disposition'));

        $this->actingAs($this->superAdmin)
            ->get(route('certificates.download', $certificate))
            ->assertOk()
            ->assertDownload($certificate->certificate_number.'.pdf');

        // Print serves exactly the generated document (modulo locale re-render).
        $this->assertStringStartsWith('%PDF', $print->getContent());
        $this->assertNotEmpty($stored);
    }

    public function test_center_admin_cannot_access_another_center_certificate(): void
    {
        $service = app(CertificateService::class);
        $certificate = $service->createAndGenerate($this->student, 'completion', $this->enrollment, $this->admin);

        $foreignCenter = Center::create(['name' => 'Beta', 'code' => 'BE', 'address' => 'B', 'status' => 'active']);
        $otherAdmin = User::factory()->centerAdmin($foreignCenter->id)->create(['status' => 'active']);

        $this->actingAs($otherAdmin)
            ->get(route('certificates.print', $certificate))
            ->assertForbidden();
    }
}
