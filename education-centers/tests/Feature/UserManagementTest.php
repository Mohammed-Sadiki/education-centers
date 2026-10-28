<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerA;
    protected Center $centerB;
    protected User $superAdmin;
    protected User $centerAdminA;
    protected User $centerAdminB;
    protected User $teacherA;
    protected User $studentA;
    protected User $teacherB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centerA = Center::factory()->create(['name' => 'Centre Berlin', 'code' => 'BERLIN']);
        $this->centerB = Center::factory()->create(['name' => 'Centre Munich', 'code' => 'MUNICH']);

        $this->superAdmin = User::factory()->superAdmin()->create([
            'email' => 'superadmin@test.de',
            'name' => 'Super Admin',
            'status' => 'active',
        ]);

        $this->centerAdminA = User::factory()->centerAdmin($this->centerA->id)->create([
            'email' => 'admin.berlin@test.de',
            'name' => 'Admin Berlin',
            'status' => 'active',
        ]);

        $this->centerAdminB = User::factory()->centerAdmin($this->centerB->id)->create([
            'email' => 'admin.munich@test.de',
            'name' => 'Admin Munich',
            'status' => 'active',
        ]);

        $this->teacherA = User::factory()->teacher($this->centerA->id)->create([
            'email' => 'teacher.berlin@test.de',
            'name' => 'Prof Berlin',
            'status' => 'active',
        ]);

        $this->studentA = User::factory()->student($this->centerA->id)->create([
            'email' => 'student.berlin@test.de',
            'name' => 'Eleve Berlin',
            'status' => 'active',
        ]);

        $this->teacherB = User::factory()->teacher($this->centerB->id)->create([
            'email' => 'teacher.munich@test.de',
            'name' => 'Prof Munich',
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_view_all_users(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('Admin Berlin');
        $response->assertSee('Admin Munich');
        $response->assertSee('Prof Berlin');
        $response->assertSee('Prof Munich');
    }

    public function test_center_admin_only_sees_users_from_their_center_excluding_students(): void
    {
        $response = $this->actingAs($this->centerAdminA)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('Prof Berlin');
        // Les élèves ne doivent JAMAIS apparaître dans la liste des utilisateurs
        $response->assertDontSee('Eleve Berlin');
        $response->assertDontSee('Prof Munich');
        $response->assertDontSee('Admin Munich');
    }

    public function test_teacher_and_student_cannot_access_user_management(): void
    {
        $responseTeacher = $this->actingAs($this->teacherA)->get(route('users.index'));
        $responseTeacher->assertStatus(403);

        $responseStudent = $this->actingAs($this->studentA)->get(route('users.index'));
        $responseStudent->assertStatus(403);
    }

    public function test_super_admin_can_create_teacher_user_with_center(): void
    {
        \App\Models\Domain::firstOrCreate(['slug' => 'informatique'], ['name' => 'Informatique', 'icon' => 'monitor', 'color' => 'blue', 'sort_order' => 1, 'is_active' => true]);
        $formation = \App\Models\Formation::create([
            'center_id' => $this->centerB->id,
            'domain' => 'informatique',
            'name' => 'Web Munich',
            'code' => 'WEB-MUC',
            'duration_hours' => 120,
            'price' => 500,
            'status' => 'active',
        ]);

        $userData = [
            'name' => 'Nouveau Prof',
            'email' => 'new.teacher@test.de',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerB->id,
            'status' => 'active',
            'domain' => 'informatique',
            'formation_id' => $formation->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('users.store'), $userData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'new.teacher@test.de',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerB->id,
            'domain' => 'informatique',
            'formation_id' => $formation->id,
        ]);
    }

    public function test_center_admin_creates_teacher_scoped_strictly_to_their_center(): void
    {
        \App\Models\Domain::firstOrCreate(['slug' => 'sante'], ['name' => 'Santé', 'icon' => 'heart', 'color' => 'emerald', 'sort_order' => 2, 'is_active' => true]);
        $formation = \App\Models\Formation::create([
            'center_id' => $this->centerA->id,
            'domain' => 'sante',
            'name' => 'Soins Berlin',
            'code' => 'SOIN-BER',
            'duration_hours' => 80,
            'price' => 400,
            'status' => 'active',
        ]);

        $userData = [
            'name' => 'Prof Cree par Admin Berlin',
            'email' => 'prof.scoped@test.de',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerB->id, // attempt spoofing
            'status' => 'active',
            'domain' => 'sante',
            'formation_id' => $formation->id,
        ];

        $response = $this->actingAs($this->centerAdminA)->post(route('users.store'), $userData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'prof.scoped@test.de',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerA->id, // MUST be centerA, not centerB!
            'domain' => 'sante',
            'formation_id' => $formation->id,
        ]);
    }

    public function test_center_admin_cannot_create_super_admin_or_center_admin(): void
    {
        $userData = [
            'name' => 'Pirate Admin',
            'email' => 'pirate@test.de',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->centerAdminA)->post(route('users.store'), $userData);
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'pirate@test.de']);
    }

    public function test_center_admin_cannot_view_or_edit_user_from_another_center(): void
    {
        // view
        $response = $this->actingAs($this->centerAdminA)->get(route('users.show', $this->teacherB));
        $response->assertStatus(403);

        // edit
        $response = $this->actingAs($this->centerAdminA)->get(route('users.edit', $this->teacherB));
        $response->assertStatus(403);

        // update
        $response = $this->actingAs($this->centerAdminA)->put(route('users.update', $this->teacherB), [
            'name' => 'Hacked Name',
            'email' => 'teacher.munich@test.de',
            'role' => User::ROLE_TEACHER,
            'status' => 'active',
        ]);
        $response->assertStatus(403);
    }

    public function test_user_can_be_updated_without_changing_password(): void
    {
        $oldPasswordHash = $this->teacherA->password;

        $response = $this->actingAs($this->centerAdminA)->put(route('users.update', $this->teacherA), [
            'name' => 'Prof Berlin Modifie',
            'email' => 'teacher.berlin@test.de',
            'role' => User::ROLE_TEACHER,
            'status' => 'active',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect();
        $this->teacherA->refresh();
        $this->assertEquals('Prof Berlin Modifie', $this->teacherA->name);
        $this->assertEquals($oldPasswordHash, $this->teacherA->password);
    }

    public function test_toggle_status_can_activate_and_deactivate_user(): void
    {
        $this->assertTrue($this->teacherA->isActive());

        // Deactivate
        $response = $this->actingAs($this->centerAdminA)->patch(route('users.toggle-status', $this->teacherA));
        $response->assertRedirect();

        $this->teacherA->refresh();
        $this->assertTrue($this->teacherA->isInactive());

        // Reactivate
        $response = $this->actingAs($this->centerAdminA)->patch(route('users.toggle-status', $this->teacherA));
        $response->assertRedirect();

        $this->teacherA->refresh();
        $this->assertTrue($this->teacherA->isActive());
    }

    public function test_center_admin_cannot_delete_users(): void
    {
        $response = $this->actingAs($this->centerAdminA)->delete(route('users.destroy', $this->teacherA));
        $response->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $this->teacherA->id]);
    }

    public function test_super_admin_can_create_teacher_user_with_domains(): void
    {
        \App\Models\Domain::firstOrCreate(['slug' => 'informatique'], ['name' => 'Informatique', 'icon' => 'monitor', 'color' => 'blue', 'sort_order' => 1, 'is_active' => true]);
        \App\Models\Domain::firstOrCreate(['slug' => 'sante'], ['name' => 'Santé', 'icon' => 'heart', 'color' => 'emerald', 'sort_order' => 2, 'is_active' => true]);

        $formation = \App\Models\Formation::create([
            'center_id' => $this->centerB->id,
            'domain' => 'informatique',
            'name' => 'Dev Web Munich',
            'code' => 'DEV-MUC',
            'duration_hours' => 120,
            'price' => 500,
            'status' => 'active',
        ]);

        $userData = [
            'name' => 'Prof Multi Domaines',
            'email' => 'multi.dom@test.de',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerB->id,
            'status' => 'active',
            'domain' => 'informatique',
            'formation_id' => $formation->id,
            'domains' => ['informatique', 'sante'],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('users.store'), $userData);

        $response->assertRedirect();
        $user = User::where('email', 'multi.dom@test.de')->first();
        $this->assertNotNull($user);
        $this->assertEqualsCanonicalizing(['informatique', 'sante'], $user->domainSlugs()->toArray());
        $this->assertDatabaseHas('user_domains', [
            'user_id' => $user->id,
            'domain_slug' => 'informatique',
        ]);
        $this->assertDatabaseHas('user_domains', [
            'user_id' => $user->id,
            'domain_slug' => 'sante',
        ]);
    }

    public function test_update_teacher_updates_teaching_domains(): void
    {
        \App\Models\Domain::firstOrCreate(['slug' => 'informatique'], ['name' => 'Informatique', 'icon' => 'monitor', 'color' => 'blue', 'sort_order' => 1, 'is_active' => true]);
        \App\Models\Domain::firstOrCreate(['slug' => 'sante'], ['name' => 'Santé', 'icon' => 'heart', 'color' => 'emerald', 'sort_order' => 2, 'is_active' => true]);

        $updateData = [
            'name' => $this->teacherA->name,
            'email' => $this->teacherA->email,
            'role' => User::ROLE_TEACHER,
            'status' => 'active',
            'domains' => ['sante'],
        ];

        $response = $this->actingAs($this->centerAdminA)->put(route('users.update', $this->teacherA), $updateData);
        $response->assertRedirect();

        $this->teacherA->refresh();
        $this->assertEqualsCanonicalizing(['sante'], $this->teacherA->domainSlugs()->toArray());
    }
}
