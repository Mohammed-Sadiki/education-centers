<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected Center $berlinCenter;
    protected Center $munichCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlinCenter = Center::create([
            'name'        => 'Centre Berlin',
            'code'        => 'BERLIN',
            'city'        => 'Berlin',
            'country'     => 'Germany',
            'status'      => 'active',
        ]);

        $this->munichCenter = Center::create([
            'name'        => 'Centre Munich',
            'code'        => 'MUNICH',
            'city'        => 'Munich',
            'country'     => 'Germany',
            'status'      => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 1. Authentification & Sessions
    // ──────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_when_accessing_centers(): void
    {
        $response = $this->get(route('centers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Connexion');
        $response->assertSee('Adresse e-mail');
        $response->assertSee('Mot de passe');
    }

    public function test_user_can_login_with_valid_credentials_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->superAdmin()->create([
            'email'    => 'admin@education-centers.de',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'admin@education-centers.de',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->superAdmin()->create([
            'email'    => 'admin@education-centers.de',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email'    => 'admin@education-centers.de',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // 2. Rôles & Accès au Dashboard
    // ──────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_access_dashboard_and_sees_all_centers_info(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Jean Super Admin',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Jean Super Admin');
        $response->assertSee('Super Admin');
        $response->assertSee('Tous les centres');
    }

    public function test_center_admin_can_access_dashboard_and_sees_their_center(): void
    {
        $berlinAdmin = User::factory()->centerAdmin($this->berlinCenter->id)->create([
            'name' => 'Klaus Admin Berlin',
        ]);

        $response = $this->actingAs($berlinAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Klaus Admin Berlin');
        $response->assertSee('Admin Centre');
        $response->assertSee('Centre Berlin');
    }

    public function test_teacher_can_access_dashboard_and_sees_their_center(): void
    {
        $teacher = User::factory()->teacher($this->berlinCenter->id)->create([
            'name' => 'Professeur Hans',
        ]);

        $response = $this->actingAs($teacher)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Professeur Hans');
        $response->assertSee('Professeur');
        $response->assertSee('Centre Berlin');
    }

    public function test_student_can_access_dashboard_and_sees_their_center(): void
    {
        $student = User::factory()->student($this->munichCenter->id)->create([
            'name' => 'Eleve Greta',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Eleve Greta');
        $response->assertSee('Élève');
        $response->assertSee('Centre Munich');
    }

    // ──────────────────────────────────────────────────────────────────────
    // 3. Protection stricte du CRUD Centers
    // ──────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_access_centers_crud(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get(route('centers.index'));
        $response->assertStatus(200);

        $responseCreate = $this->actingAs($superAdmin)->get(route('centers.create'));
        $responseCreate->assertStatus(200);
    }

    public function test_center_admin_cannot_access_centers_crud(): void
    {
        $berlinAdmin = User::factory()->centerAdmin($this->berlinCenter->id)->create();

        $responseIndex = $this->actingAs($berlinAdmin)->get(route('centers.index'));
        $responseIndex->assertStatus(403);

        $responseCreate = $this->actingAs($berlinAdmin)->get(route('centers.create'));
        $responseCreate->assertStatus(403);

        $responseStore = $this->actingAs($berlinAdmin)->post(route('centers.store'), [
            'name' => 'Nouveau Centre Tente',
            'code' => 'TENTE',
            'city' => 'Hambourg',
            'country' => 'Germany',
            'status' => 'active',
        ]);
        $responseStore->assertStatus(403);

        $responseEdit = $this->actingAs($berlinAdmin)->get(route('centers.edit', $this->berlinCenter));
        $responseEdit->assertStatus(403);

        $responseDelete = $this->actingAs($berlinAdmin)->delete(route('centers.destroy', $this->berlinCenter));
        $responseDelete->assertStatus(403);
    }

    public function test_teacher_cannot_access_centers_crud(): void
    {
        $teacher = User::factory()->teacher($this->berlinCenter->id)->create();

        $response = $this->actingAs($teacher)->get(route('centers.index'));
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_centers_crud(): void
    {
        $student = User::factory()->student($this->munichCenter->id)->create();

        $response = $this->actingAs($student)->get(route('centers.index'));
        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 4. Règles de cohérence et méthodes helpers
    // ──────────────────────────────────────────────────────────────────────

    public function test_user_role_helper_methods(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isCenterAdmin());
        $this->assertFalse($superAdmin->isTeacher());
        $this->assertFalse($superAdmin->isStudent());

        $centerAdmin = User::factory()->centerAdmin($this->berlinCenter->id)->create();
        $this->assertFalse($centerAdmin->isSuperAdmin());
        $this->assertTrue($centerAdmin->isCenterAdmin());
        $this->assertFalse($centerAdmin->isTeacher());
        $this->assertFalse($centerAdmin->isStudent());

        $teacher = User::factory()->teacher($this->berlinCenter->id)->create();
        $this->assertFalse($teacher->isSuperAdmin());
        $this->assertFalse($teacher->isCenterAdmin());
        $this->assertTrue($teacher->isTeacher());
        $this->assertFalse($teacher->isStudent());

        $student = User::factory()->student($this->munichCenter->id)->create();
        $this->assertFalse($student->isSuperAdmin());
        $this->assertFalse($student->isCenterAdmin());
        $this->assertFalse($student->isTeacher());
        $this->assertTrue($student->isStudent());
    }

    public function test_super_admin_always_has_null_center_id(): void
    {
        $superAdmin = new User();
        $superAdmin->name = 'Super Admin Strict';
        $superAdmin->email = 'superstrict@centers.de';
        $superAdmin->password = Hash::make('password123');
        $superAdmin->role = User::ROLE_SUPER_ADMIN;
        $superAdmin->center_id = 999; // Tentative d'assignation
        $superAdmin->save();

        // Le booted hook doit garantir center_id = null
        $this->assertNull($superAdmin->fresh()->center_id);
    }

    public function test_center_users_cannot_be_saved_without_center_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        $user->name = 'Admin Sans Centre';
        $user->email = 'sanscentre@centers.de';
        $user->password = Hash::make('password123');
        $user->role = User::ROLE_CENTER_ADMIN;
        $user->center_id = null; // Doit lever une exception
        $user->save();
    }

    public function test_admin_berlin_belongs_to_berlin_and_not_munich(): void
    {
        $berlinAdmin = User::factory()->centerAdmin($this->berlinCenter->id)->create();

        $this->assertTrue($berlinAdmin->belongsToCenter($this->berlinCenter->id));
        $this->assertFalse($berlinAdmin->belongsToCenter($this->munichCenter->id));
    }
}
