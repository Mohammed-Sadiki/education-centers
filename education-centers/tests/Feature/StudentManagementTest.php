<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerBerlin;
    protected Center $centerMunich;
    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;
    protected User $teacherBerlin;
    protected User $studentUserBerlin;
    protected User $studentUserMunich;
    protected Student $studentBerlin;
    protected Student $studentMunich;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Centres
        $this->centerBerlin = Center::factory()->create(['name' => 'Centre Berlin', 'code' => 'BER']);
        $this->centerMunich = Center::factory()->create(['name' => 'Centre Munich', 'code' => 'MUN']);

        // 2. Super Admin
        $this->superAdmin = User::factory()->superAdmin()->create([
            'email' => 'superadmin@test.de',
            'status' => 'active',
        ]);

        // 3. Admin Centre Berlin
        $this->adminBerlin = User::factory()->centerAdmin($this->centerBerlin->id)->create([
            'email' => 'admin.berlin@test.de',
            'status' => 'active',
        ]);

        // 4. Admin Centre Munich
        $this->adminMunich = User::factory()->centerAdmin($this->centerMunich->id)->create([
            'email' => 'admin.munich@test.de',
            'status' => 'active',
        ]);

        // 5. Professeur Berlin
        $this->teacherBerlin = User::factory()->teacher($this->centerBerlin->id)->create([
            'email' => 'teacher.berlin@test.de',
            'status' => 'active',
        ]);

        // 6. Utilisateurs élèves
        $this->studentUserBerlin = User::factory()->student($this->centerBerlin->id)->create([
            'name' => 'Jean Berlin',
            'email' => 'jean.berlin@test.de',
            'status' => 'active',
        ]);

        $this->studentUserMunich = User::factory()->student($this->centerMunich->id)->create([
            'name' => 'Hans Munich',
            'email' => 'hans.munich@test.de',
            'status' => 'active',
        ]);

        // 7. Dossiers étudiants existants
        $this->studentBerlin = Student::create([
            'user_id' => $this->studentUserBerlin->id,
            'center_id' => $this->centerBerlin->id,
            'student_number' => 'BER-000001',
            'first_name' => 'Jean',
            'last_name' => 'Berlin',
            'date_of_birth' => '2000-01-15',
            'city' => 'Berlin',
            'country' => 'Germany',
            'status' => 'active',
        ]);

        $this->studentMunich = Student::create([
            'user_id' => $this->studentUserMunich->id,
            'center_id' => $this->centerMunich->id,
            'student_number' => 'MUN-000001',
            'first_name' => 'Hans',
            'last_name' => 'Munich',
            'date_of_birth' => '1999-06-20',
            'city' => 'Munich',
            'country' => 'Germany',
            'status' => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Super Admin Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_all_students_and_filter_by_center(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('students.index'));
        $response->assertOk();
        $response->assertSee('BER-000001');
        $response->assertSee('MUN-000001');

        // Filtrer par centre Berlin
        $filterResponse = $this->actingAs($this->superAdmin)->get(route('students.index', ['center_id' => $this->centerBerlin->id]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('BER-000001');
        $filterResponse->assertDontSee('MUN-000001');
    }

    public function test_super_admin_can_create_student_record_for_any_center(): void
    {
        $newUser = User::factory()->student($this->centerMunich->id)->create([
            'name' => 'Sophie Munich',
            'email' => 'sophie.munich@test.de',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('students.store'), [
            'user_id' => $newUser->id,
            'center_id' => $this->centerMunich->id,
            'first_name' => 'Sophie',
            'last_name' => 'Munich',
            'date_of_birth' => '2001-04-12',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'user_id' => $newUser->id,
            'center_id' => $this->centerMunich->id,
            'first_name' => 'Sophie',
            'last_name' => 'Munich',
        ]);
    }

    public function test_super_admin_can_view_and_update_any_student(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('students.show', $this->studentMunich));
        $response->assertOk();
        $response->assertSee('MUN-000001');

        $updateResponse = $this->actingAs($this->superAdmin)->put(route('students.update', $this->studentMunich), [
            'first_name' => 'Hans-Updated',
            'last_name' => 'Munich-Updated',
            'status' => 'active',
        ]);

        $updateResponse->assertRedirect(route('students.show', $this->studentMunich));
        $this->assertDatabaseHas('students', [
            'id' => $this->studentMunich->id,
            'first_name' => 'Hans-Updated',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Admin Centre Tests & Multi-Center Isolation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_center_admin_can_only_view_students_of_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('students.index'));
        $response->assertOk();
        $response->assertSee('BER-000001');
        $response->assertDontSee('MUN-000001');
    }

    public function test_center_admin_cannot_consult_student_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('students.show', $this->studentMunich));
        $response->assertForbidden();
    }

    public function test_center_admin_cannot_edit_or_update_student_of_another_center(): void
    {
        // GET edit
        $editResponse = $this->actingAs($this->adminBerlin)->get(route('students.edit', $this->studentMunich));
        $editResponse->assertForbidden();

        // PUT update
        $updateResponse = $this->actingAs($this->adminBerlin)->put(route('students.update', $this->studentMunich), [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();
        $this->assertDatabaseMissing('students', ['first_name' => 'Hacked']);
    }

    public function test_center_admin_can_create_student_for_own_center(): void
    {
        $newStudentUser = User::factory()->student($this->centerBerlin->id)->create([
            'name' => 'Clara Berlin',
            'email' => 'clara.berlin@test.de',
        ]);

        $response = $this->actingAs($this->adminBerlin)->post(route('students.store'), [
            'user_id' => $newStudentUser->id,
            'first_name' => 'Clara',
            'last_name' => 'Berlin',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'user_id' => $newStudentUser->id,
            'center_id' => $this->centerBerlin->id,
            'first_name' => 'Clara',
        ]);
    }

    public function test_center_admin_cannot_create_student_for_another_center(): void
    {
        $munichStudentUser = User::factory()->student($this->centerMunich->id)->create([
            'name' => 'Lukas Munich',
            'email' => 'lukas.munich@test.de',
        ]);

        // Admin Berlin tente de créer un dossier pour cet élève de Munich
        $response = $this->actingAs($this->adminBerlin)->post(route('students.store'), [
            'user_id' => $munichStudentUser->id,
            'first_name' => 'Lukas',
            'last_name' => 'Munich',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('students', ['user_id' => $munichStudentUser->id]);
    }

    public function test_center_admin_cannot_spoof_center_id_in_store(): void
    {
        $newBerlinStudentUser = User::factory()->student($this->centerBerlin->id)->create([
            'name' => 'Anna Berlin',
            'email' => 'anna.berlin@test.de',
        ]);

        // Admin Berlin tente d'injecter center_id de Munich
        $response = $this->actingAs($this->adminBerlin)->post(route('students.store'), [
            'user_id' => $newBerlinStudentUser->id,
            'center_id' => $this->centerMunich->id,
            'first_name' => 'Anna',
            'last_name' => 'Berlin',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        // Le serveur doit avoir forcé le center_id à Berlin
        $this->assertDatabaseHas('students', [
            'user_id' => $newBerlinStudentUser->id,
            'center_id' => $this->centerBerlin->id,
        ]);
        $this->assertDatabaseMissing('students', [
            'user_id' => $newBerlinStudentUser->id,
            'center_id' => $this->centerMunich->id,
        ]);
    }

    public function test_cannot_create_student_for_non_student_role(): void
    {
        // Tentative de créer un dossier pour un enseignant
        $response = $this->actingAs($this->adminBerlin)->post(route('students.store'), [
            'user_id' => $this->teacherBerlin->id,
            'first_name' => 'Prof',
            'last_name' => 'Frauduleux',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('students', ['user_id' => $this->teacherBerlin->id]);
    }

    public function test_cannot_create_duplicate_student_record_for_same_user(): void
    {
        // studentBerlin a déjà un dossier étudiant
        $response = $this->actingAs($this->adminBerlin)->post(route('students.store'), [
            'user_id' => $this->studentUserBerlin->id,
            'first_name' => 'Jean',
            'last_name' => 'Bis',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Role Permissions: Teacher & Student Access Denied (403)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_teacher_is_forbidden_from_student_management(): void
    {
        $response = $this->actingAs($this->teacherBerlin)->get(route('students.index'));
        $response->assertForbidden();

        $createResponse = $this->actingAs($this->teacherBerlin)->get(route('students.create'));
        $createResponse->assertForbidden();

        $showResponse = $this->actingAs($this->teacherBerlin)->get(route('students.show', $this->studentBerlin));
        $showResponse->assertForbidden();
    }

    public function test_student_is_forbidden_from_student_management(): void
    {
        $response = $this->actingAs($this->studentUserBerlin)->get(route('students.index'));
        $response->assertForbidden();

        $showResponse = $this->actingAs($this->studentUserBerlin)->get(route('students.show', $this->studentBerlin));
        $showResponse->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Toggle Status & Student Number Generation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_toggle_student_status(): void
    {
        $this->assertTrue($this->studentBerlin->isActive());

        $response = $this->actingAs($this->adminBerlin)->patch(route('students.toggle-status', $this->studentBerlin));
        $response->assertRedirect();

        $this->studentBerlin->refresh();
        $this->assertTrue($this->studentBerlin->isInactive());
    }

    public function test_admin_cannot_toggle_student_status_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->patch(route('students.toggle-status', $this->studentMunich));
        $response->assertForbidden();
    }

    public function test_student_number_is_auto_generated_and_unique(): void
    {
        $user1 = User::factory()->student($this->centerBerlin->id)->create();
        $user2 = User::factory()->student($this->centerBerlin->id)->create();

        $this->actingAs($this->adminBerlin)->post(route('students.store'), [
            'user_id' => $user1->id,
            'first_name' => 'Student',
            'last_name' => 'One',
            'status' => 'active',
        ]);

        $this->actingAs($this->adminBerlin)->post(route('students.store'), [
            'user_id' => $user2->id,
            'first_name' => 'Student',
            'last_name' => 'Two',
            'status' => 'active',
        ]);

        $student1 = Student::where('user_id', $user1->id)->first();
        $student2 = Student::where('user_id', $user2->id)->first();

        $this->assertNotNull($student1->student_number);
        $this->assertNotNull($student2->student_number);
        $this->assertNotEquals($student1->student_number, $student2->student_number);
        $this->assertStringStartsWith('BER-', $student1->student_number);
    }
}
