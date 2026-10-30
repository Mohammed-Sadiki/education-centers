<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerBerlin;
    protected Center $centerMunich;
    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;
    protected User $teacherUserBerlin;
    protected User $teacherUserMunich;
    protected User $studentUserBerlin;
    protected Teacher $teacherBerlin;
    protected Teacher $teacherMunich;

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

        // 3. Admin Centre Berlin & Munich
        $this->adminBerlin = User::factory()->centerAdmin($this->centerBerlin->id)->create([
            'email' => 'admin.berlin@test.de',
            'status' => 'active',
        ]);

        $this->adminMunich = User::factory()->centerAdmin($this->centerMunich->id)->create([
            'email' => 'admin.munich@test.de',
            'status' => 'active',
        ]);

        // 4. Utilisateurs professeurs
        $this->teacherUserBerlin = User::factory()->teacher($this->centerBerlin->id)->create([
            'name' => 'Professeur Berlin',
            'email' => 'prof.berlin@test.de',
            'status' => 'active',
        ]);

        $this->teacherUserMunich = User::factory()->teacher($this->centerMunich->id)->create([
            'name' => 'Professeur Munich',
            'email' => 'prof.munich@test.de',
            'status' => 'active',
        ]);

        // 5. Utilisateur élève
        $this->studentUserBerlin = User::factory()->student($this->centerBerlin->id)->create([
            'name' => 'Eleve Berlin',
            'email' => 'eleve.berlin@test.de',
            'status' => 'active',
        ]);

        // 6. Dossiers professeurs initiaux
        $this->teacherBerlin = Teacher::create([
            'user_id' => $this->teacherUserBerlin->id,
            'center_id' => $this->centerBerlin->id,
            'teacher_number' => 'BER-T-000001',
            'first_name' => 'Johannes',
            'last_name' => 'Kohl',
            'date_of_birth' => '1985-03-12',
            'city' => 'Berlin',
            'country' => 'Germany',
            'specialization' => 'Allemand',
            'qualification' => 'Master',
            'hire_date' => '2022-09-01',
            'status' => 'active',
        ]);

        $this->teacherMunich = Teacher::create([
            'user_id' => $this->teacherUserMunich->id,
            'center_id' => $this->centerMunich->id,
            'teacher_number' => 'MUN-T-000001',
            'first_name' => 'Greta',
            'last_name' => 'Schneider',
            'date_of_birth' => '1988-11-05',
            'city' => 'München',
            'country' => 'Germany',
            'specialization' => 'Mathématiques',
            'qualification' => 'Doctorat',
            'hire_date' => '2023-01-15',
            'status' => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Super Admin Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_all_teachers_and_filter_by_center(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('teachers.index'));
        $response->assertOk();
        $response->assertSee('BER-T-000001');
        $response->assertSee('MUN-T-000001');

        // Filtrer par centre Berlin
        $filterResponse = $this->actingAs($this->superAdmin)->get(route('teachers.index', ['center_id' => $this->centerBerlin->id]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('BER-T-000001');
        $filterResponse->assertDontSee('MUN-T-000001');
    }

    public function test_super_admin_can_create_teacher_record_for_any_center(): void
    {
        $newTeacherUser = User::factory()->teacher($this->centerMunich->id)->create([
            'name' => 'Albrecht Dürer',
            'email' => 'albrecht@test.de',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('teachers.store'), [
            'user_id' => $newTeacherUser->id,
            'center_id' => $this->centerMunich->id,
            'first_name' => 'Albrecht',
            'last_name' => 'Dürer',
            'specialization' => 'Arts & Histoire',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teachers', [
            'user_id' => $newTeacherUser->id,
            'center_id' => $this->centerMunich->id,
            'first_name' => 'Albrecht',
            'last_name' => 'Dürer',
        ]);
    }

    public function test_super_admin_can_view_and_update_any_teacher(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('teachers.show', $this->teacherMunich));
        $response->assertOk();
        $response->assertSee('MUN-T-000001');

        $updateResponse = $this->actingAs($this->superAdmin)->put(route('teachers.update', $this->teacherMunich), [
            'first_name' => 'Greta-Updated',
            'last_name' => 'Schneider',
            'specialization' => 'Physique Quantique',
            'status' => 'active',
        ]);

        $updateResponse->assertRedirect(route('teachers.show', $this->teacherMunich));
        $this->assertDatabaseHas('teachers', [
            'id' => $this->teacherMunich->id,
            'first_name' => 'Greta-Updated',
            'specialization' => 'Physique Quantique',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Admin Centre Tests & Isolation Multi-Centres
    // ──────────────────────────────────────────────────────────────────────────

    public function test_center_admin_can_only_view_teachers_of_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('teachers.index'));
        $response->assertOk();
        $response->assertSee('BER-T-000001');
        $response->assertDontSee('MUN-T-000001');
    }

    public function test_center_admin_cannot_consult_teacher_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('teachers.show', $this->teacherMunich));
        $response->assertForbidden();
    }

    public function test_center_admin_cannot_edit_or_update_teacher_of_another_center(): void
    {
        $editResponse = $this->actingAs($this->adminBerlin)->get(route('teachers.edit', $this->teacherMunich));
        $editResponse->assertForbidden();

        $updateResponse = $this->actingAs($this->adminBerlin)->put(route('teachers.update', $this->teacherMunich), [
            'first_name' => 'Hacked Teacher',
            'last_name' => 'Name',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();
        $this->assertDatabaseMissing('teachers', ['first_name' => 'Hacked Teacher']);
    }

    public function test_center_admin_cannot_create_teacher(): void
    {
        $newBerlinTeacher = User::factory()->teacher($this->centerBerlin->id)->create([
            'name' => 'Stefan Zweig',
            'email' => 'stefan@test.de',
        ]);

        $response = $this->actingAs($this->adminBerlin)->post(route('teachers.store'), [
            'user_id' => $newBerlinTeacher->id,
            'first_name' => 'Stefan',
            'last_name' => 'Zweig',
            'specialization' => 'Littérature',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teachers', [
            'user_id' => $newBerlinTeacher->id,
            'first_name' => 'Stefan',
        ]);
    }

    public function test_super_admin_can_create_teacher(): void
    {
        $newBerlinTeacher = User::factory()->teacher($this->centerBerlin->id)->create([
            'name' => 'Stefan Zweig',
            'email' => 'stefan@test.de',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('teachers.store'), [
            'user_id' => $newBerlinTeacher->id,
            'center_id' => $this->centerBerlin->id,
            'first_name' => 'Stefan',
            'last_name' => 'Zweig',
            'specialization' => 'Littérature',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teachers', [
            'user_id' => $newBerlinTeacher->id,
            'center_id' => $this->centerBerlin->id,
            'first_name' => 'Stefan',
        ]);
    }

    public function test_center_admin_cannot_create_teacher_for_another_center(): void
    {
        $munichTeacherUser = User::factory()->teacher($this->centerMunich->id)->create([
            'name' => 'Karl Munich',
            'email' => 'karl.munich@test.de',
        ]);

        $response = $this->actingAs($this->adminBerlin)->post(route('teachers.store'), [
            'user_id' => $munichTeacherUser->id,
            'first_name' => 'Karl',
            'last_name' => 'Munich',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teachers', ['user_id' => $munichTeacherUser->id]);
    }

    public function test_center_admin_cannot_spoof_center_id_in_store(): void
    {
        $newBerlinTeacher = User::factory()->teacher($this->centerBerlin->id)->create([
            'name' => 'Hanna Berlin',
            'email' => 'hanna.berlin@test.de',
        ]);

        // Tentative d'envoyer center_id de Munich par Admin Centre -> refusé 403
        $response = $this->actingAs($this->adminBerlin)->post(route('teachers.store'), [
            'user_id' => $newBerlinTeacher->id,
            'center_id' => $this->centerMunich->id,
            'first_name' => 'Hanna',
            'last_name' => 'Berlin',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teachers', [
            'user_id' => $newBerlinTeacher->id,
        ]);
    }

    public function test_cannot_create_teacher_for_non_teacher_role(): void
    {
        // Tentative de créer un dossier professeur pour un étudiant par Super Admin
        $response = $this->actingAs($this->superAdmin)->post(route('teachers.store'), [
            'user_id' => $this->studentUserBerlin->id,
            'first_name' => 'Faux',
            'last_name' => 'Prof',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('teachers', ['user_id' => $this->studentUserBerlin->id]);
    }

    public function test_cannot_create_duplicate_teacher_record_for_same_user(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('teachers.store'), [
            'user_id' => $this->teacherUserBerlin->id,
            'first_name' => 'Johannes',
            'last_name' => 'Bis',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Role Permissions: Teacher & Student Access Denied (403)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_teacher_is_forbidden_from_teacher_management(): void
    {
        $response = $this->actingAs($this->teacherUserBerlin)->get(route('teachers.index'));
        $response->assertForbidden();

        $createResponse = $this->actingAs($this->teacherUserBerlin)->get(route('teachers.create'));
        $createResponse->assertForbidden();

        $showResponse = $this->actingAs($this->teacherUserBerlin)->get(route('teachers.show', $this->teacherBerlin));
        $showResponse->assertForbidden();
    }

    public function test_student_is_forbidden_from_teacher_management(): void
    {
        $response = $this->actingAs($this->studentUserBerlin)->get(route('teachers.index'));
        $response->assertForbidden();

        $showResponse = $this->actingAs($this->studentUserBerlin)->get(route('teachers.show', $this->teacherBerlin));
        $showResponse->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Toggle Status & Number Generation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_toggle_teacher_status(): void
    {
        $this->assertTrue($this->teacherBerlin->isActive());

        $response = $this->actingAs($this->adminBerlin)->patch(route('teachers.toggle-status', $this->teacherBerlin));
        $response->assertRedirect();

        $this->teacherBerlin->refresh();
        $this->assertTrue($this->teacherBerlin->isInactive());
    }

    public function test_admin_cannot_toggle_teacher_status_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->patch(route('teachers.toggle-status', $this->teacherMunich));
        $response->assertForbidden();
    }

    public function test_teacher_number_is_auto_generated_and_unique(): void
    {
        $user1 = User::factory()->teacher($this->centerBerlin->id)->create();
        $user2 = User::factory()->teacher($this->centerBerlin->id)->create();

        $this->actingAs($this->superAdmin)->post(route('teachers.store'), [
            'user_id' => $user1->id,
            'center_id' => $this->centerBerlin->id,
            'first_name' => 'Prof',
            'last_name' => 'Un',
            'status' => 'active',
        ]);

        $this->actingAs($this->superAdmin)->post(route('teachers.store'), [
            'user_id' => $user2->id,
            'center_id' => $this->centerBerlin->id,
            'first_name' => 'Prof',
            'last_name' => 'Deux',
            'status' => 'active',
        ]);

        $t1 = Teacher::where('user_id', $user1->id)->first();
        $t2 = Teacher::where('user_id', $user2->id)->first();

        $this->assertNotNull($t1->teacher_number);
        $this->assertNotNull($t2->teacher_number);
        $this->assertNotEquals($t1->teacher_number, $t2->teacher_number);
        $this->assertStringStartsWith('BER-T-', $t1->teacher_number);
    }
}
