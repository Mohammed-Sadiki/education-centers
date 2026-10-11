<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;
    protected User $studentUserBerlin;
    protected User $studentUserMunich;
    protected Center $berlin;
    protected Center $munich;
    protected Formation $formationBerlin;
    protected Formation $formationMunich;
    protected ClassRoom $classBerlin;
    protected ClassRoom $classMunich;
    protected Student $studentBerlin;
    protected Student $studentMunich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::create([
            'name' => 'Centre Berlin', 'code' => 'BER', 'address' => 'Alexanderplatz 1', 'status' => 'active',
        ]);
        $this->munich = Center::create([
            'name' => 'Centre Munich', 'code' => 'MUN', 'address' => 'Marienplatz 1', 'status' => 'active',
        ]);

        $this->formationBerlin = Formation::create([
            'center_id' => $this->berlin->id, 'name' => 'Deutsch A1', 'code' => 'BER-A1',
            'duration_hours' => 100, 'price' => 400, 'currency' => 'EUR', 'status' => 'active',
        ]);
        $this->formationMunich = Formation::create([
            'center_id' => $this->munich->id, 'name' => 'Deutsch A1 Munich', 'code' => 'MUN-A1',
            'duration_hours' => 100, 'price' => 400, 'currency' => 'EUR', 'status' => 'active',
        ]);

        $this->classBerlin = ClassRoom::create([
            'center_id' => $this->berlin->id, 'formation_id' => $this->formationBerlin->id,
            'name' => 'Classe Berlin A1-01', 'code' => 'A1-01', 'capacity' => 5, 'status' => 'active',
        ]);
        $this->classMunich = ClassRoom::create([
            'center_id' => $this->munich->id, 'formation_id' => $this->formationMunich->id,
            'name' => 'Classe Munich A1-01', 'code' => 'A1-01', 'capacity' => 5, 'status' => 'active',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin', 'email' => 'super@plateforme.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN, 'center_id' => null,
        ]);
        $this->adminBerlin = User::create([
            'name' => 'Admin Berlin', 'email' => 'admin@berlin.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_CENTER_ADMIN, 'center_id' => $this->berlin->id,
        ]);
        $this->adminMunich = User::create([
            'name' => 'Admin Munich', 'email' => 'admin@munich.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_CENTER_ADMIN, 'center_id' => $this->munich->id,
        ]);

        $this->studentUserBerlin = User::create([
            'name' => 'Student Berlin', 'email' => 'student@berlin.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_STUDENT, 'center_id' => $this->berlin->id,
        ]);
        $this->studentUserMunich = User::create([
            'name' => 'Student Munich', 'email' => 'student@munich.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_STUDENT, 'center_id' => $this->munich->id,
        ]);

        $this->studentBerlin = Student::create([
            'user_id' => $this->studentUserBerlin->id, 'center_id' => $this->berlin->id,
            'student_number' => 'BER-000001', 'first_name' => 'Alice', 'last_name' => 'Muller',
            'date_of_birth' => '2000-01-15', 'gender' => 'female', 'nationality' => 'DE', 'status' => 'active',
        ]);
        $this->studentMunich = Student::create([
            'user_id' => $this->studentUserMunich->id, 'center_id' => $this->munich->id,
            'student_number' => 'MUN-000001', 'first_name' => 'Bob', 'last_name' => 'Schmidt',
            'date_of_birth' => '2001-03-20', 'gender' => 'male', 'nationality' => 'DE', 'status' => 'active',
        ]);
    }

    private function makeEnrollment(array $overrides = []): Enrollment
    {
        return Enrollment::create(array_merge([
            'center_id'           => $this->berlin->id,
            'student_id'          => $this->studentBerlin->id,
            'formation_id'        => $this->formationBerlin->id,
            'class_id'            => null,
            'registration_number' => Enrollment::generateRegistrationNumber($this->berlin),
            'application_date'    => '2026-01-10',
            'status'              => Enrollment::STATUS_PENDING,
        ], $overrides));
    }

    // BLOC 1 — ACCES ET AUTORISATION

    public function test_01_guest_cannot_access_enrollment_index(): void
    {
        $this->get(route('enrollments.index'))->assertRedirect(route('login'));
    }

    public function test_02_super_admin_can_access_enrollment_index(): void
    {
        $this->actingAs($this->superAdmin)->get(route('enrollments.index'))->assertOk()->assertViewIs('enrollments.index');
    }

    public function test_03_center_admin_can_access_enrollment_index(): void
    {
        $this->actingAs($this->adminBerlin)->get(route('enrollments.index'))->assertOk()->assertViewIs('enrollments.index');
    }

    public function test_04_student_user_cannot_access_enrollment_index(): void
    {
        $this->actingAs($this->studentUserBerlin)->get(route('enrollments.index'))->assertStatus(403);
    }

    public function test_05_center_admin_sees_only_own_center_enrollments(): void
    {
        $enrollmentBerlin = $this->makeEnrollment();
        $enrollmentMunich = $this->makeEnrollment([
            'center_id' => $this->munich->id, 'student_id' => $this->studentMunich->id,
            'formation_id' => $this->formationMunich->id,
            'registration_number' => Enrollment::generateRegistrationNumber($this->munich),
        ]);

        $response = $this->actingAs($this->adminBerlin)->get(route('enrollments.index'));
        $response->assertOk();
        $enrollments = $response->viewData('enrollments');
        $this->assertTrue($enrollments->contains($enrollmentBerlin));
        $this->assertFalse($enrollments->contains($enrollmentMunich));
    }

    public function test_06_super_admin_sees_all_centers_enrollments(): void
    {
        $enrollmentBerlin = $this->makeEnrollment();
        $enrollmentMunich = $this->makeEnrollment([
            'center_id' => $this->munich->id, 'student_id' => $this->studentMunich->id,
            'formation_id' => $this->formationMunich->id,
            'registration_number' => Enrollment::generateRegistrationNumber($this->munich),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('enrollments.index'));
        $response->assertOk();
        $enrollments = $response->viewData('enrollments');
        $this->assertTrue($enrollments->contains($enrollmentBerlin));
        $this->assertTrue($enrollments->contains($enrollmentMunich));
    }

    // BLOC 2 — CREATION (STORE)

    public function test_07_center_admin_can_access_create_form(): void
    {
        $this->actingAs($this->adminBerlin)->get(route('enrollments.create'))->assertOk()->assertViewIs('enrollments.create');
    }

    public function test_08_center_admin_can_create_enrollment(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-01-15', 'notes' => 'Test inscription.',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'center_id' => $this->berlin->id, 'status' => Enrollment::STATUS_PENDING,
        ]);
    }

    public function test_09_enrollment_initial_status_is_pending(): void
    {
        $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-01-15',
        ]);
        $this->assertDatabaseHas('enrollments', ['student_id' => $this->studentBerlin->id, 'status' => Enrollment::STATUS_PENDING]);
    }

    public function test_10_registration_number_is_generated_automatically(): void
    {
        $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-01-15',
        ]);
        $enrollment = Enrollment::where('student_id', $this->studentBerlin->id)->first();
        $this->assertNotNull($enrollment->registration_number);
        $this->assertStringStartsWith('BER-INS-', $enrollment->registration_number);
    }

    public function test_11_center_id_is_forced_server_side_for_center_admin(): void
    {
        $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'center_id' => $this->munich->id, 'student_id' => $this->studentBerlin->id,
            'formation_id' => $this->formationBerlin->id, 'application_date' => '2026-01-15',
        ]);
        $this->assertDatabaseHas('enrollments', ['student_id' => $this->studentBerlin->id, 'center_id' => $this->berlin->id]);
        $this->assertDatabaseMissing('enrollments', ['student_id' => $this->studentBerlin->id, 'center_id' => $this->munich->id]);
    }

    public function test_12_center_admin_cannot_enroll_student_from_other_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentMunich->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors(['student_id']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_13_center_admin_cannot_enroll_to_formation_from_other_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationMunich->id,
            'application_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors(['formation_id']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_14_duplicate_active_enrollment_is_rejected(): void
    {
        $this->makeEnrollment(['status' => Enrollment::STATUS_PENDING]);
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-01-20',
        ]);
        $response->assertSessionHasErrors(['student_id']);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_15_student_can_be_reenrolled_after_rejected_or_cancelled(): void
    {
        $this->makeEnrollment(['status' => Enrollment::STATUS_REJECTED]);
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-02-01',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('enrollments', 2);
    }

    public function test_16_application_date_is_required(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
        ]);
        $response->assertSessionHasErrors(['application_date']);
    }

    public function test_17_inactive_student_cannot_be_enrolled(): void
    {
        $this->studentBerlin->update(['status' => 'inactive']);
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors(['student_id']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_18_inactive_formation_cannot_receive_enrollment(): void
    {
        $this->formationBerlin->update(['status' => 'inactive']);
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors(['formation_id']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_19_class_must_belong_to_same_center_and_formation(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'class_id' => $this->classMunich->id, 'application_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors(['class_id']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    // BLOC 3 — CONSULTATION (SHOW)

    public function test_20_center_admin_can_view_own_enrollment(): void
    {
        $enrollment = $this->makeEnrollment();
        $this->actingAs($this->adminBerlin)->get(route('enrollments.show', $enrollment))
            ->assertOk()->assertViewIs('enrollments.show');
    }

    public function test_21_center_admin_cannot_view_enrollment_from_other_center(): void
    {
        $enrollmentMunich = $this->makeEnrollment([
            'center_id' => $this->munich->id, 'student_id' => $this->studentMunich->id,
            'formation_id' => $this->formationMunich->id,
            'registration_number' => Enrollment::generateRegistrationNumber($this->munich),
        ]);
        $this->actingAs($this->adminBerlin)->get(route('enrollments.show', $enrollmentMunich))->assertStatus(403);
    }

    public function test_22_super_admin_can_view_any_enrollment(): void
    {
        $enrollmentBerlin = $this->makeEnrollment();
        $enrollmentMunich = $this->makeEnrollment([
            'center_id' => $this->munich->id, 'student_id' => $this->studentMunich->id,
            'formation_id' => $this->formationMunich->id,
            'registration_number' => Enrollment::generateRegistrationNumber($this->munich),
        ]);
        $this->actingAs($this->superAdmin)->get(route('enrollments.show', $enrollmentBerlin))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('enrollments.show', $enrollmentMunich))->assertOk();
    }

    // BLOC 4 — MISE A JOUR (EDIT / UPDATE)

    public function test_23_center_admin_can_update_own_enrollment(): void
    {
        $enrollment = $this->makeEnrollment();
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.update', $enrollment), [
            'student_id' => $this->studentBerlin->id, 'formation_id' => $this->formationBerlin->id,
            'application_date' => '2026-02-01', 'notes' => 'Notes mises a jour.',
        ]);
        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'notes' => 'Notes mises a jour.']);
    }

    public function test_24_center_admin_cannot_update_enrollment_from_other_center(): void
    {
        $enrollmentMunich = $this->makeEnrollment([
            'center_id' => $this->munich->id, 'student_id' => $this->studentMunich->id,
            'formation_id' => $this->formationMunich->id,
            'registration_number' => Enrollment::generateRegistrationNumber($this->munich),
        ]);
        $this->actingAs($this->adminBerlin)->patch(route('enrollments.update', $enrollmentMunich), [
            'student_id' => $this->studentMunich->id, 'formation_id' => $this->formationMunich->id,
            'application_date' => '2026-02-01',
        ])->assertStatus(403);
    }

    // BLOC 5 — SUPPRESSION (DESTROY)

    public function test_25_center_admin_can_delete_own_enrollment(): void
    {
        $enrollment = $this->makeEnrollment();
        $response = $this->actingAs($this->adminBerlin)->delete(route('enrollments.destroy', $enrollment));
        $response->assertRedirect(route('enrollments.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_26_center_admin_cannot_delete_enrollment_from_other_center(): void
    {
        $enrollmentMunich = $this->makeEnrollment([
            'center_id' => $this->munich->id, 'student_id' => $this->studentMunich->id,
            'formation_id' => $this->formationMunich->id,
            'registration_number' => Enrollment::generateRegistrationNumber($this->munich),
        ]);
        $this->actingAs($this->adminBerlin)->delete(route('enrollments.destroy', $enrollmentMunich))->assertStatus(403);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollmentMunich->id]);
    }

    // BLOC 6 — WORKFLOW DES STATUTS

    public function test_27_transition_pending_to_under_review(): void
    {
        $enrollment = $this->makeEnrollment();
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $enrollment), [
            'status' => Enrollment::STATUS_UNDER_REVIEW,
        ]);
        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => Enrollment::STATUS_UNDER_REVIEW]);
    }

    public function test_28_transition_under_review_to_accepted_with_class(): void
    {
        $enrollment = $this->makeEnrollment(['status' => Enrollment::STATUS_UNDER_REVIEW]);
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $enrollment), [
            'status' => Enrollment::STATUS_ACCEPTED, 'class_id' => $this->classBerlin->id,
        ]);
        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id, 'status' => Enrollment::STATUS_ACCEPTED, 'class_id' => $this->classBerlin->id,
        ]);
        $this->assertNotNull(Enrollment::find($enrollment->id)->accepted_at);
    }

    public function test_29_transition_pending_to_rejected(): void
    {
        $enrollment = $this->makeEnrollment();
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $enrollment), [
            'status' => Enrollment::STATUS_REJECTED, 'decision_notes' => 'Dossier incomplet.',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $fresh = $enrollment->fresh();
        $this->assertEquals(Enrollment::STATUS_REJECTED, $fresh->status);
        $this->assertEquals('Dossier incomplet.', $fresh->decision_notes);
        $this->assertNotNull($fresh->decision_date);
    }

    public function test_30_transition_accepted_to_cancelled(): void
    {
        $enrollment = $this->makeEnrollment(['status' => Enrollment::STATUS_ACCEPTED, 'class_id' => $this->classBerlin->id]);
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $enrollment), [
            'status' => Enrollment::STATUS_CANCELLED,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $fresh = $enrollment->fresh();
        $this->assertEquals(Enrollment::STATUS_CANCELLED, $fresh->status);
        $this->assertNotNull($fresh->cancelled_at);
    }

    public function test_31_invalid_transition_rejected_to_accepted_is_forbidden(): void
    {
        $enrollment = $this->makeEnrollment(['status' => Enrollment::STATUS_REJECTED]);
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $enrollment), [
            'status' => Enrollment::STATUS_ACCEPTED,
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['status']);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => Enrollment::STATUS_REJECTED]);
    }

    public function test_32_invalid_transition_cancelled_to_pending_is_forbidden(): void
    {
        $enrollment = $this->makeEnrollment(['status' => Enrollment::STATUS_CANCELLED]);
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $enrollment), [
            'status' => Enrollment::STATUS_PENDING,
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['status']);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => Enrollment::STATUS_CANCELLED]);
    }

    public function test_33_center_admin_cannot_change_status_of_other_center_enrollment(): void
    {
        $enrollmentMunich = $this->makeEnrollment([
            'center_id' => $this->munich->id, 'student_id' => $this->studentMunich->id,
            'formation_id' => $this->formationMunich->id,
            'registration_number' => Enrollment::generateRegistrationNumber($this->munich),
        ]);
        $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $enrollmentMunich), [
            'status' => Enrollment::STATUS_UNDER_REVIEW,
        ])->assertStatus(403);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollmentMunich->id, 'status' => Enrollment::STATUS_PENDING]);
    }

    // BLOC 7 — VERIFICATION DE CAPACITE

    public function test_34_acceptance_is_blocked_when_class_is_full(): void
    {
        $capacity = $this->classBerlin->capacity;
        for ($i = 0; $i < $capacity; $i++) {
            $user = User::create([
                'name' => "Student Extra $i", 'email' => "student.extra.$i@berlin.test",
                'password' => bcrypt('password'), 'role' => User::ROLE_STUDENT, 'center_id' => $this->berlin->id,
            ]);
            $student = Student::create([
                'user_id' => $user->id, 'center_id' => $this->berlin->id, 'student_number' => "BER-EXT-$i",
                'first_name' => 'Student', 'last_name' => "Extra $i", 'date_of_birth' => '2000-01-01',
                'gender' => 'male', 'nationality' => 'DE', 'status' => 'active',
            ]);
            Enrollment::create([
                'center_id' => $this->berlin->id, 'student_id' => $student->id,
                'formation_id' => $this->formationBerlin->id, 'class_id' => $this->classBerlin->id,
                'registration_number' => Enrollment::generateRegistrationNumber($this->berlin),
                'application_date' => '2026-01-10', 'status' => Enrollment::STATUS_ACCEPTED,
            ]);
        }

        $newEnrollment = $this->makeEnrollment(['status' => Enrollment::STATUS_UNDER_REVIEW]);
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $newEnrollment), [
            'status' => Enrollment::STATUS_ACCEPTED, 'class_id' => $this->classBerlin->id,
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['class_id']);
        $this->assertDatabaseHas('enrollments', ['id' => $newEnrollment->id, 'status' => Enrollment::STATUS_UNDER_REVIEW]);
    }

    public function test_35_null_capacity_means_unlimited(): void
    {
        $this->classBerlin->update(['capacity' => null]);

        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => "Student Unl $i", 'email' => "student.unl.$i@berlin.test",
                'password' => bcrypt('password'), 'role' => User::ROLE_STUDENT, 'center_id' => $this->berlin->id,
            ]);
            $student = Student::create([
                'user_id' => $user->id, 'center_id' => $this->berlin->id, 'student_number' => "BER-UNL-$i",
                'first_name' => 'Student', 'last_name' => "Unlimited $i", 'date_of_birth' => '2000-01-01',
                'gender' => 'male', 'nationality' => 'DE', 'status' => 'active',
            ]);
            Enrollment::create([
                'center_id' => $this->berlin->id, 'student_id' => $student->id,
                'formation_id' => $this->formationBerlin->id, 'class_id' => $this->classBerlin->id,
                'registration_number' => Enrollment::generateRegistrationNumber($this->berlin),
                'application_date' => '2026-01-10', 'status' => Enrollment::STATUS_ACCEPTED,
            ]);
        }

        $newEnrollment = $this->makeEnrollment(['status' => Enrollment::STATUS_UNDER_REVIEW]);
        $response = $this->actingAs($this->adminBerlin)->patch(route('enrollments.change-status', $newEnrollment), [
            'status' => Enrollment::STATUS_ACCEPTED, 'class_id' => $this->classBerlin->id,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', ['id' => $newEnrollment->id, 'status' => Enrollment::STATUS_ACCEPTED]);
    }

    // BLOC 8 — SERVICE METIER (EnrollmentStatusService)

    public function test_36_can_transition_returns_true_for_valid_transitions(): void
    {
        $service = new EnrollmentStatusService();
        $this->assertTrue($service->canTransition('pending', 'under_review'));
        $this->assertTrue($service->canTransition('pending', 'cancelled'));
        $this->assertTrue($service->canTransition('pending', 'rejected'));
        $this->assertTrue($service->canTransition('under_review', 'accepted'));
        $this->assertTrue($service->canTransition('under_review', 'rejected'));
        $this->assertTrue($service->canTransition('under_review', 'cancelled'));
        $this->assertTrue($service->canTransition('accepted', 'cancelled'));
    }

    public function test_37_can_transition_returns_false_for_invalid_transitions(): void
    {
        $service = new EnrollmentStatusService();
        $this->assertFalse($service->canTransition('rejected', 'accepted'));
        $this->assertFalse($service->canTransition('cancelled', 'pending'));
        $this->assertFalse($service->canTransition('accepted', 'pending'));
        $this->assertFalse($service->canTransition('rejected', 'pending'));
        $this->assertFalse($service->canTransition('cancelled', 'under_review'));
    }

    public function test_38_change_status_throws_exception_for_invalid_transition(): void
    {
        $service = new EnrollmentStatusService();
        $enrollment = $this->makeEnrollment(['status' => Enrollment::STATUS_REJECTED]);
        $this->expectException(ValidationException::class);
        $service->changeStatus($enrollment, Enrollment::STATUS_ACCEPTED);
    }

    // BLOC 9 — NUMERO D'INSCRIPTION

    public function test_39_registration_numbers_are_sequential_per_center_and_globally_unique(): void
    {
        $numbers = [];
        for ($i = 0; $i < 3; $i++) {
            $user = User::create([
                'name' => "Student Seq $i", 'email' => "student.seq.$i@berlin.test",
                'password' => bcrypt('password'), 'role' => User::ROLE_STUDENT, 'center_id' => $this->berlin->id,
            ]);
            $student = Student::create([
                'user_id' => $user->id, 'center_id' => $this->berlin->id, 'student_number' => "BER-SEQ-$i",
                'first_name' => 'Student', 'last_name' => "Seq $i", 'date_of_birth' => '2000-01-01',
                'gender' => 'male', 'nationality' => 'DE', 'status' => 'active',
            ]);
            $this->actingAs($this->adminBerlin)->post(route('enrollments.store'), [
                'student_id' => $student->id, 'formation_id' => $this->formationBerlin->id,
                'application_date' => '2026-01-15',
            ]);
            $created = Enrollment::where('student_id', $student->id)->first();
            $numbers[] = $created->registration_number;
        }

        foreach ($numbers as $num) {
            $this->assertStringStartsWith('BER-INS-', $num);
        }
        $this->assertCount(3, array_unique($numbers));

        $this->actingAs($this->adminMunich)->post(route('enrollments.store'), [
            'student_id' => $this->studentMunich->id, 'formation_id' => $this->formationMunich->id,
            'application_date' => '2026-01-15',
        ]);
        $munichEnrollment = Enrollment::where('student_id', $this->studentMunich->id)->first();
        $this->assertStringStartsWith('MUN-INS-', $munichEnrollment->registration_number);
    }
}
