<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Models\User;
use App\Services\PersonalFinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suivi des paiements élèves et salaires professeurs.
 *
 * Couvre les 15 scénarios obligatoires : situations d'élève (payé, partiel,
 * en retard, sans échéancier, multi-formations), mise à jour après versement,
 * situations de salaire (versé, partiel, en attente, en retard), absence de
 * doublons, confidentialité, isolation entre centres, traduction FR/AR et
 * non-régression du module Finance.
 */
class PersonalFinanceTest extends TestCase
{
    use RefreshDatabase;

    private Center $berlin;

    private Center $munich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::create([
            'name' => 'Centre Berlin', 'code' => 'BER', 'address' => 'Adresse Berlin', 'status' => 'active',
        ]);
        $this->munich = Center::create([
            'name' => 'Centre Munich', 'code' => 'MUN', 'address' => 'Adresse Munich', 'status' => 'active',
        ]);
    }

    // ── 1. Élève ayant tout payé ─────────────────────────────────────────
    public function test_student_who_paid_everything_is_marked_paid(): void
    {
        [$student, $formation, $enrollment] = $this->studentWithEnrollment($this->berlin, 'BER-STU-001', 12000);

        $this->makePayment($student, $enrollment, $formation, 12000);

        $situation = app(PersonalFinanceService::class)->studentSituation($student);

        $this->assertSame('paid', $situation['status']);
        $this->assertSame(12000.0, $situation['total_expected']);
        $this->assertSame(12000.0, $situation['total_paid']);
        $this->assertSame(0.0, $situation['total_remaining']);
        $this->assertFalse($situation['has_alert']);
    }

    // ── 2. Élève ayant payé partiellement ────────────────────────────────
    public function test_student_partial_payment_is_not_paid(): void
    {
        [$student, $formation, $enrollment] = $this->studentWithEnrollment($this->berlin, 'BER-STU-002', 12000);

        // Un versement partiel ne doit JAMAIS rendre l'élève « à jour ».
        $this->makePayment($student, $enrollment, $formation, 4500);

        $situation = app(PersonalFinanceService::class)->studentSituation($student);

        $this->assertSame('partial', $situation['status']);
        $this->assertSame(7500.0, $situation['total_remaining']);
    }

    // ── 3. Élève avec mensualité en retard ───────────────────────────────
    public function test_student_with_overdue_echeance_triggers_alert(): void
    {
        [$student, $formation, $enrollment] = $this->studentWithEnrollment($this->berlin, 'BER-STU-003', 12000);

        $this->makeInvoice($student, $enrollment, $formation, 'INV-OVERDUE', 4500, now()->subDays(10)->toDateString(), 'sent');

        $situation = app(PersonalFinanceService::class)->studentSituation($student);

        $this->assertSame('overdue', $situation['status']);
        $this->assertTrue($situation['has_alert']);
        $this->assertCount(1, $situation['overdue_invoices']);
        $this->assertSame(4500.0, $situation['overdue_invoices']->first()->amount);
    }

    // ── 4. Élève sans échéancier défini ──────────────────────────────────
    public function test_student_without_schedule_has_no_invented_overdue_date(): void
    {
        [$student, $formation, $enrollment] = $this->studentWithEnrollment($this->berlin, 'BER-STU-004', 12000);

        $this->makePayment($student, $enrollment, $formation, 3000);

        $situation = app(PersonalFinanceService::class)->studentSituation($student);

        // Aucune facture ⇒ pas de prochaine échéance inventée, pas d'alerte.
        $this->assertNull($situation['next_echeance']);
        $this->assertFalse($situation['has_alert']);
        $this->assertNotSame('overdue', $situation['status']);
        $this->assertSame(9000.0, $situation['total_remaining']);
    }

    // ── 5. Élève inscrit à plusieurs formations ──────────────────────────
    public function test_student_with_multiple_formations_has_per_enrollment_rows(): void
    {
        $user = User::factory()->student($this->berlin->id)->create();
        $student = $this->makeStudent($user, $this->berlin, 'BER-STU-005');

        $f1 = $this->makeFormation($this->berlin, 'BER-F1', 12000);
        $f2 = $this->makeFormation($this->berlin, 'BER-F2', 9000);

        $e1 = $this->makeEnrollment($student, $f1, 'BER-INS-101');
        $e2 = $this->makeEnrollment($student, $f2, 'BER-INS-102');

        $this->makePayment($student, $e1, $f1, 12000);
        $this->makePayment($student, $e2, $f2, 3000);

        $situation = app(PersonalFinanceService::class)->studentSituation($student);

        $this->assertCount(2, $situation['enrollments']);
        $this->assertSame(21000.0, $situation['total_expected']);
        $this->assertSame(15000.0, $situation['total_paid']);
        $this->assertSame(6000.0, $situation['total_remaining']);
    }

    // ── 6. Mise à jour après versement ───────────────────────────────────
    public function test_status_updates_after_new_payment(): void
    {
        [$student, $formation, $enrollment] = $this->studentWithEnrollment($this->berlin, 'BER-STU-006', 12000);

        $this->makePayment($student, $enrollment, $formation, 6000);
        $before = app(PersonalFinanceService::class)->studentSituation($student);
        $this->assertSame('partial', $before['status']);

        // L'admin enregistre un second versement : tout est recalculé à la volée.
        $this->makePayment($student, $enrollment, $formation, 6000);
        $after = app(PersonalFinanceService::class)->studentSituation($student);

        $this->assertSame('paid', $after['status']);
        $this->assertSame(0.0, $after['total_remaining']);
    }

    // ── 7. Professeur ayant reçu son salaire ─────────────────────────────
    public function test_teacher_fully_paid_salary(): void
    {
        $teacher = $this->makeTeacher($this->berlin, 'BER-T-007');
        $this->makeSalary($teacher, now()->month, now()->year, 5000, 5000, 'paid', now()->toDateString());

        $situation = app(PersonalFinanceService::class)->teacherSituation($teacher);

        $this->assertSame('paid', $situation['status']);
        $this->assertSame(0.0, $situation['total_remaining']);
        $this->assertFalse($situation['has_alert']);
    }

    // ── 8. Professeur avec salaire partiellement versé ───────────────────
    public function test_teacher_partially_paid_salary(): void
    {
        $teacher = $this->makeTeacher($this->berlin, 'BER-T-008');
        // Date prévue future ⇒ partiel, jamais « en retard ».
        $this->makeSalary($teacher, now()->month, now()->year, 5000, 2000, 'partial', null, now()->addDays(20)->toDateString());

        $situation = app(PersonalFinanceService::class)->teacherSituation($teacher);

        $this->assertSame('partial', $situation['status']);
        $this->assertSame(3000.0, $situation['total_remaining']);
        $this->assertFalse($situation['has_alert']);
    }

    // ── 9. Professeur avec salaire en attente (sans date prévue) ─────────
    public function test_teacher_pending_salary_without_date_is_never_overdue(): void
    {
        $teacher = $this->makeTeacher($this->berlin, 'BER-T-009');
        // Aucune date prévue : ne doit JAMAIS être « en retard ».
        $this->makeSalary($teacher, now()->month, now()->year, 5000, 0, 'unpaid', null, null);

        $situation = app(PersonalFinanceService::class)->teacherSituation($teacher);

        $this->assertSame('pending', $situation['status']);
        $this->assertNotSame('overdue', $situation['status']);
        $this->assertFalse($situation['has_alert']);
    }

    // ── 10. Professeur avec salaire en retard ────────────────────────────
    public function test_teacher_overdue_salary_triggers_alert(): void
    {
        $teacher = $this->makeTeacher($this->berlin, 'BER-T-010');
        // Date prévue dépassée et reste dû ⇒ en retard + alerte.
        $this->makeSalary($teacher, now()->month, now()->year, 5000, 0, 'unpaid', null, now()->subDays(5)->toDateString());

        $situation = app(PersonalFinanceService::class)->teacherSituation($teacher);

        $this->assertSame('overdue', $situation['status']);
        $this->assertTrue($situation['has_alert']);
        $this->assertCount(1, $situation['overdue_salaries']);
    }

    // ── 11. Absence de doublons dans les calculs ─────────────────────────
    public function test_payment_linked_to_enrollment_is_counted_once(): void
    {
        $user = User::factory()->student($this->berlin->id)->create();
        $student = $this->makeStudent($user, $this->berlin, 'BER-STU-011');
        $formation = $this->makeFormation($this->berlin, 'BER-F11', 12000);
        $enrollment = $this->makeEnrollment($student, $formation, 'BER-INS-011');

        // Un paiement rattaché à l'inscription (et à la même formation) ne
        // doit être comptabilisé qu'une seule fois.
        $this->makePayment($student, $enrollment, $formation, 5000);

        $situation = app(PersonalFinanceService::class)->studentSituation($student);

        $this->assertSame(5000.0, $situation['total_paid']);
        $this->assertSame(1, $situation['enrollments']->first()->payments->count());
    }

    // ── 12. Confidentialité des données ──────────────────────────────────
    public function test_student_cannot_view_other_student_payments_via_url(): void
    {
        // Autre élève du même centre, avec une situation financière propre.
        [$otherUser, $other] = $this->studentPair($this->berlin, 'BER-STU-012B');
        $otherFormation = $this->makeFormation($this->berlin, 'BER-F12B', 12000);
        $otherEnrollment = $this->makeEnrollment($other, $otherFormation, 'BER-INS-012B');
        $this->makePayment($other, $otherEnrollment, $otherFormation, 1000);

        // Élève connecté, distinct.
        [$user, ] = $this->studentPair($this->berlin, 'BER-STU-012C');

        // Tentative d'accès au dossier d'un autre élève via l'URL → 403.
        $this->actingAs($user)
            ->get(route('student.payments', ['student_id' => $other->id]))
            ->assertForbidden();
    }

    public function test_teacher_cannot_view_other_teacher_salaries_via_url(): void
    {
        $other = $this->makeTeacher($this->berlin, 'BER-T-012D');
        $this->makeSalary($other, now()->month, now()->year, 5000, 5000, 'paid', now()->toDateString());

        $user = User::factory()->teacher($this->berlin->id)->create();
        $this->makeTeacher($this->berlin, 'BER-T-012E', $user);

        $this->actingAs($user)
            ->get(route('teacher.salaries', ['teacher_id' => $other->id]))
            ->assertForbidden();
    }

    public function test_student_payments_page_shows_only_own_data(): void
    {
        [$user, $student] = $this->studentPair($this->berlin, 'BER-STU-012F');
        $formation = $this->makeFormation($this->berlin, 'BER-F12F', 12000);
        $enrollment = $this->makeEnrollment($student, $formation, 'BER-INS-012F');
        $this->makePayment($student, $enrollment, $formation, 12000);

        // Un autre élève du même centre, avec une facture en retard tierce.
        [$otherUser, $other] = $this->studentPair($this->berlin, 'BER-STU-012G');
        $otherFormation = $this->makeFormation($this->berlin, 'BER-F12G', 12000);
        $otherEnrollment = $this->makeEnrollment($other, $otherFormation, 'BER-INS-012G');
        $this->makeInvoice($other, $otherEnrollment, $otherFormation, 'INV-SECRET-12G', 4500, now()->subDays(5)->toDateString(), 'sent');

        $this->actingAs($user)
            ->get(route('student.payments'))
            ->assertOk()
            ->assertSee('Formation BER-F12F')
            ->assertDontSee('INV-SECRET-12G')
            ->assertDontSee('BER-INS-012G')
            ->assertDontSee('Formation BER-F12G');
    }

    // ── 13. Isolation entre centres (Berlin / Munich) ────────────────────
    public function test_center_isolation_between_berlin_and_munich(): void
    {
        // Élève de Munich avec une situation propre.
        [$munichUser, $munichStudent] = $this->studentPair($this->munich, 'MUN-STU-013');
        $munichFormation = $this->makeFormation($this->munich, 'MUN-F13', 9800);
        $munichEnrollment = $this->makeEnrollment($munichStudent, $munichFormation, 'MUN-INS-013');
        $this->makePayment($munichStudent, $munichEnrollment, $munichFormation, 9800);

        $situation = app(PersonalFinanceService::class)->studentSituation($munichStudent);
        $this->assertSame(9800.0, $situation['total_expected']);

        // L'élève de Berlin ne voit aucune donnée Munich.
        [$berlinUser, $berlinStudent] = $this->studentPair($this->berlin, 'BER-STU-013');
        $berlinFormation = $this->makeFormation($this->berlin, 'BER-F13', 12000);
        $this->makeEnrollment($berlinStudent, $berlinFormation, 'BER-INS-013');

        $this->actingAs($berlinUser)
            ->get(route('student.payments'))
            ->assertOk()
            ->assertDontSee('MUN-INS-013')
            ->assertDontSee('MUN-F13');
    }

    // ── 14. Traduction FR / AR ───────────────────────────────────────────
    public function test_pages_render_in_french_and_arabic(): void
    {
        [$user, $student] = $this->studentPair($this->berlin, 'BER-STU-014');
        $formation = $this->makeFormation($this->berlin, 'BER-F14', 12000);
        $enrollment = $this->makeEnrollment($student, $formation, 'BER-INS-014');
        $this->makePayment($student, $enrollment, $formation, 12000);

        $this->actingAs($user)
            ->get(route('student.payments'))
            ->assertOk()
            ->assertSee('Mes paiements');

        $this->actingAs($user)
            ->get(route('student.payments', ['lang' => 'ar']))
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    public function test_teacher_salaries_page_renders(): void
    {
        $user = User::factory()->teacher($this->berlin->id)->create();
        $teacher = $this->makeTeacher($this->berlin, 'BER-T-014', $user);
        $this->makeSalary($teacher, now()->month, now()->year, 5000, 5000, 'paid', now()->toDateString());

        $this->actingAs($user)
            ->get(route('teacher.salaries'))
            ->assertOk()
            ->assertSee('Mes salaires');
    }

    // ── 15. Non-régression du module Finance ─────────────────────────────
    public function test_finance_module_unchanged_and_service_reads_exist(): void
    {
        $formation = $this->makeFormation($this->berlin, 'BER-F15', 12000);

        // Le prix attendu réutilise bien le module Finance existant.
        $this->assertSame(12000.0, \App\Services\FinanceService::getFormationAnnualPrice($formation));

        // Les relations personnelles réutilisent les tables Finance sans dupliquer.
        [, $student] = $this->studentPair($this->berlin, 'BER-STU-015');
        $enrollment = $this->makeEnrollment($student, $formation, 'BER-INS-015');
        $this->makePayment($student, $enrollment, $formation, 1000);
        $this->makeInvoice($student, $enrollment, $formation, 'INV-F15', 12000, now()->addDays(10)->toDateString(), 'sent');

        $this->assertCount(1, $enrollment->payments);
        $this->assertCount(1, $enrollment->invoices);
    }

    public function test_portal_dashboard_includes_finance_summary(): void
    {
        [$user, $student] = $this->studentPair($this->berlin, 'BER-STU-015B');
        $formation = $this->makeFormation($this->berlin, 'BER-F15B', 12000);
        $enrollment = $this->makeEnrollment($student, $formation, 'BER-INS-015B');
        $this->makePayment($student, $enrollment, $formation, 12000);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Situation financi');
    }

    // ════════════════════════════════════════════════════════════════════
    //  Helpers
    // ════════════════════════════════════════════════════════════════════

    /**
     * @return array{0: Student, 1: Formation, 2: Enrollment}
     */
    private function studentWithEnrollment(Center $center, string $number, float $price): array
    {
        [$user, $student] = $this->studentPair($center, $number);
        $formation = $this->makeFormation($center, $number.'-F', $price);
        $enrollment = $this->makeEnrollment($student, $formation, $number.'-INS');

        return [$student, $formation, $enrollment];
    }

    /**
     * @return array{0: User, 1: Student}
     */
    private function studentPair(Center $center, string $number): array
    {
        $user = User::factory()->student($center->id)->create([
            'email' => strtolower($number).'@example.test',
        ]);
        $student = $this->makeStudent($user, $center, $number);

        return [$user, $student];
    }

    private function makeStudent(User $user, Center $center, string $number): Student
    {
        return Student::create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'student_number' => $number,
            'first_name' => 'Prénom',
            'last_name' => $number,
            'status' => 'active',
        ]);
    }

    private function makeFormation(Center $center, string $code, float $annualPrice): Formation
    {
        return Formation::create([
            'center_id' => $center->id,
            'domain' => 'informatique',
            'name' => 'Formation '.$code,
            'code' => $code,
            'duration_weeks' => 24,
            'duration_hours' => 600,
            'price' => $annualPrice,
            'annual_price' => $annualPrice,
            'currency' => 'MAD',
            'status' => 'active',
        ]);
    }

    private function makeEnrollment(Student $student, Formation $formation, string $registrationNumber): Enrollment
    {
        return Enrollment::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'formation_id' => $formation->id,
            'registration_number' => $registrationNumber,
            'application_date' => now()->subWeeks(2)->toDateString(),
            'status' => 'accepted',
            'accepted_at' => now()->subWeek(),
        ]);
    }

    private function makePayment(Student $student, ?Enrollment $enrollment, Formation $formation, float $amount): Payment
    {
        static $seq = 0;
        $seq++;

        return Payment::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment?->id,
            'formation_id' => $formation->id,
            'amount' => $amount,
            'currency' => 'MAD',
            'payment_date' => now()->subDays($seq)->toDateString(),
            'method' => 'cash',
            'reference' => 'PAYTEST-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
            'status' => 'paid',
        ]);
    }

    private function makeInvoice(Student $student, ?Enrollment $enrollment, Formation $formation, string $number, float $amount, ?string $dueDate, string $status): Invoice
    {
        return Invoice::create([
            'center_id' => $student->center_id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment?->id,
            'formation_id' => $formation->id,
            'invoice_number' => $number,
            'total_amount' => $amount,
            'currency' => 'MAD',
            'invoice_date' => now()->subDays(30)->toDateString(),
            'due_date' => $dueDate,
            'status' => $status,
        ]);
    }

    private function makeTeacher(Center $center, string $number, ?User $user = null): Teacher
    {
        $user ??= User::factory()->teacher($center->id)->create([
            'email' => strtolower($number).'@example.test',
        ]);

        return Teacher::create([
            'user_id' => $user->id,
            'center_id' => $center->id,
            'teacher_number' => $number,
            'first_name' => 'Prof',
            'last_name' => $number,
            'status' => 'active',
        ]);
    }

    private function makeSalary(Teacher $teacher, int $month, int $year, float $expected, float $paid, string $status, ?string $paymentDate, ?string $scheduledDate = null): TeacherSalary
    {
        return TeacherSalary::create([
            'center_id' => $teacher->center_id,
            'teacher_id' => $teacher->id,
            'domain' => 'informatique',
            'month' => $month,
            'year' => $year,
            'school_year' => '2026/2027',
            'expected_amount' => $expected,
            'paid_amount' => $paid,
            'currency' => 'MAD',
            'status' => $status,
            'payment_date' => $paymentDate,
            'scheduled_payment_date' => $scheduledDate,
        ]);
    }
}
