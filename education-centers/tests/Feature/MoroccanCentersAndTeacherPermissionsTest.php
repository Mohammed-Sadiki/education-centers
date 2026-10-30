<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Domain;
use App\Models\Formation;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoroccanCentersAndTeacherPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerTanger;
    protected Center $centerEssaouira;
    protected Center $centerAgadir;
    protected Center $centerTetouane;

    protected User $superAdmin;
    protected User $adminTanger;
    protected User $adminEssaouira;
    protected User $adminAgadir;
    protected User $adminTetouane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centerTanger = Center::factory()->create([
            'name' => 'Centre Tanger',
            'code' => 'TANGER',
            'city' => 'Tanger',
            'country' => 'Morocco',
        ]);

        $this->centerEssaouira = Center::factory()->create([
            'name' => 'Centre Essaouira',
            'code' => 'ESSAOUIRA',
            'city' => 'Essaouira',
            'country' => 'Morocco',
        ]);

        $this->centerAgadir = Center::factory()->create([
            'name' => 'Centre Agadir',
            'code' => 'AGADIR',
            'city' => 'Agadir',
            'country' => 'Morocco',
        ]);

        $this->centerTetouane = Center::factory()->create([
            'name' => 'Centre Tétouane',
            'code' => 'TETOUANE',
            'city' => 'Tétouane',
            'country' => 'Morocco',
        ]);

        $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'superadmin@ec.ma']);
        $this->adminTanger = User::factory()->centerAdmin($this->centerTanger->id)->create(['email' => 'admin.tanger@ec.ma']);
        $this->adminEssaouira = User::factory()->centerAdmin($this->centerEssaouira->id)->create(['email' => 'admin.essaouira@ec.ma']);
        $this->adminAgadir = User::factory()->centerAdmin($this->centerAgadir->id)->create(['email' => 'admin.agadir@ec.ma']);
        $this->adminTetouane = User::factory()->centerAdmin($this->centerTetouane->id)->create(['email' => 'admin.tetouane@ec.ma']);
    }

    /**
     * Test 1: Les 4 centres existent et sont disponibles pour le Super Admin
     */
    public function test_all_four_moroccan_centers_are_available_to_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('centers.index'));
        $response->assertStatus(200);
        $response->assertSee('Centre Tanger');
        $response->assertSee('Centre Essaouira');
        $response->assertSee('Centre Agadir');
        $response->assertSee('Centre Tétouane');
    }

    /**
     * Test 2: Un Admin Centre ne peut pas voir ou accéder aux centres des autres
     */
    public function test_admin_center_cannot_access_centers_crud(): void
    {
        $response = $this->actingAs($this->adminTanger)->get(route('centers.index'));
        $response->assertStatus(403);
    }

    /**
     * Test 3: Un Admin Centre ne peut pas accéder au formulaire de création de professeur
     */
    public function test_admin_center_forbidden_from_teacher_creation_form(): void
    {
        $responseUsersCreate = $this->actingAs($this->adminTanger)->get(route('users.create'));
        $responseUsersCreate->assertStatus(403);

        $responseTeachersCreate = $this->actingAs($this->adminTanger)->get(route('teachers.create'));
        $responseTeachersCreate->assertStatus(403);
    }

    /**
     * Test 4: Le bouton Ajouter un utilisateur n'apparaît pas pour l'Admin Centre
     */
    public function test_add_user_button_not_rendered_for_center_admin(): void
    {
        $response = $this->actingAs($this->adminTanger)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertDontSee(route('users.create'));
        $response->assertDontSee('+ Ajouter un utilisateur');
    }

    /**
     * Test 5: Le bouton Ajouter un utilisateur apparaît bien pour le Super Admin
     */
    public function test_add_user_button_rendered_for_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee(route('users.create'));
    }

    /**
     * Test 6: Requête POST directe bloquée côté serveur (403) pour l'Admin Centre
     */
    public function test_post_creation_of_teacher_blocked_server_side_for_center_admin(): void
    {
        Domain::firstOrCreate(['slug' => 'informatique'], ['name' => 'Informatique', 'icon' => 'monitor', 'color' => 'blue', 'sort_order' => 1, 'is_active' => true]);
        $formation = Formation::create([
            'center_id' => $this->centerTanger->id,
            'domain' => 'informatique',
            'name' => 'Web Tanger',
            'code' => 'WEB-TNG',
            'duration_hours' => 100,
            'price' => 500,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Professeur Direct',
            'email' => 'direct.prof@test.ma',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_TEACHER,
            'status' => 'active',
            'domain' => 'informatique',
            'formation_id' => $formation->id,
        ];

        // Tentative via users.store
        $response = $this->actingAs($this->adminTanger)->post(route('users.store'), $payload);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'direct.prof@test.ma']);

        // Tentative via teachers.store
        $teacherUser = User::factory()->teacher($this->centerTanger->id)->create(['email' => 'user.tanger@test.ma']);
        $responseTeacher = $this->actingAs($this->adminTanger)->post(route('teachers.store'), [
            'user_id' => $teacherUser->id,
            'first_name' => 'Prof',
            'last_name' => 'Tanger',
            'status' => 'active',
        ]);
        $responseTeacher->assertStatus(403);
        $this->assertDatabaseMissing('teachers', ['user_id' => $teacherUser->id]);
    }

    /**
     * Test 7: Super Admin peut créer un professeur avec la sélection dynamique Centre -> Domaine -> Formation
     */
    public function test_super_admin_can_create_teacher_successfully(): void
    {
        Domain::firstOrCreate(['slug' => 'informatique'], ['name' => 'Informatique', 'icon' => 'monitor', 'color' => 'blue', 'sort_order' => 1, 'is_active' => true]);
        $formationAgadir = Formation::create([
            'center_id' => $this->centerAgadir->id,
            'domain' => 'informatique',
            'name' => 'Cloud & Réseau Agadir',
            'code' => 'NET-AGA',
            'duration_hours' => 120,
            'price' => 600,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Professeur Agadir',
            'email' => 'prof.agadir@ec.ma',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerAgadir->id,
            'status' => 'active',
            'domain' => 'informatique',
            'formation_id' => $formationAgadir->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('users.store'), $payload);
        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'prof.agadir@ec.ma',
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->centerAgadir->id,
            'domain' => 'informatique',
            'formation_id' => $formationAgadir->id,
        ]);
    }

    /**
     * Test 8: Les Admin Centres peuvent consulter les professeurs de leur centre, mais pas des autres
     */
    public function test_center_admin_can_consult_teachers_strictly_scoped_to_their_center(): void
    {
        $teacherUserTanger = User::factory()->teacher($this->centerTanger->id)->create(['name' => 'Prof Tanger']);
        $teacherTanger = Teacher::create([
            'user_id' => $teacherUserTanger->id,
            'center_id' => $this->centerTanger->id,
            'teacher_number' => 'TNG-T-000001',
            'first_name' => 'Mohamed',
            'last_name' => 'Tanger',
            'status' => 'active',
        ]);

        $teacherUserEssaouira = User::factory()->teacher($this->centerEssaouira->id)->create(['name' => 'Prof Essaouira']);
        $teacherEssaouira = Teacher::create([
            'user_id' => $teacherUserEssaouira->id,
            'center_id' => $this->centerEssaouira->id,
            'teacher_number' => 'ESS-T-000001',
            'first_name' => 'Fatima',
            'last_name' => 'Essaouira',
            'status' => 'active',
        ]);

        // L'admin Tanger consulte la liste : il voit son professeur
        $responseTanger = $this->actingAs($this->adminTanger)->get(route('teachers.index'));
        $responseTanger->assertStatus(200);
        $responseTanger->assertSee('Mohamed');
        $responseTanger->assertDontSee('Fatima');

        // L'admin Tanger consulte la fiche de son professeur : 200
        $responseShowOwn = $this->actingAs($this->adminTanger)->get(route('teachers.show', $teacherTanger));
        $responseShowOwn->assertStatus(200);

        // L'admin Tanger tente de consulter la fiche du professeur d'Essaouira : 403
        $responseShowOther = $this->actingAs($this->adminTanger)->get(route('teachers.show', $teacherEssaouira));
        $responseShowOther->assertStatus(403);
    }
}

