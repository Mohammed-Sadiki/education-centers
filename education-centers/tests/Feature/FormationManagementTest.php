<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Center $centerBerlin;
    protected Center $centerMunich;
    protected User $superAdmin;
    protected User $adminBerlin;
    protected User $adminMunich;
    protected User $teacherBerlin;
    protected User $studentBerlin;
    protected Formation $formationBerlin;
    protected Formation $formationMunich;

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

        // 4. Professeur & Élève
        $this->teacherBerlin = User::factory()->teacher($this->centerBerlin->id)->create([
            'email' => 'teacher.berlin@test.de',
            'status' => 'active',
        ]);

        $this->studentBerlin = User::factory()->student($this->centerBerlin->id)->create([
            'email' => 'student.berlin@test.de',
            'status' => 'active',
        ]);

        // 5. Formations initiales
        $this->formationBerlin = Formation::create([
            'center_id'      => $this->centerBerlin->id,
            'name'           => 'Deutsch A1 Berlin',
            'code'           => 'DE-A1',
            'description'    => 'Cours d’allemand débutant',
            'duration_hours' => 120,
            'duration_weeks' => 8,
            'price'          => 450.00,
            'currency'       => 'EUR',
            'status'         => 'active',
        ]);

        $this->formationMunich = Formation::create([
            'center_id'      => $this->centerMunich->id,
            'name'           => 'English B2 Munich',
            'code'           => 'EN-B2',
            'description'    => 'Cours d’anglais intermédiaire',
            'duration_hours' => 100,
            'duration_weeks' => 6,
            'price'          => 500.00,
            'currency'       => 'EUR',
            'status'         => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Super Admin Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_all_formations_and_filter_by_center(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('formations.index'));
        $response->assertOk();
        $response->assertSee('Deutsch A1 Berlin');
        $response->assertSee('English B2 Munich');

        // Filtrer par centre Berlin
        $filterResponse = $this->actingAs($this->superAdmin)->get(route('formations.index', ['center_id' => $this->centerBerlin->id]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Deutsch A1 Berlin');
        $filterResponse->assertDontSee('English B2 Munich');
    }

    public function test_super_admin_can_create_formation_for_any_center(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('formations.store'), [
            'center_id'      => $this->centerMunich->id,
            'domain'         => 'informatique',
            'name'           => 'Python Basics',
            'code'           => 'PY-101',
            'description'    => 'Introduction to Python',
            'duration_hours' => 40,
            'duration_weeks' => 4,
            'price'          => 300.00,
            'currency'       => 'EUR',
            'status'         => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('formations', [
            'center_id' => $this->centerMunich->id,
            'domain'    => 'informatique',
            'code'      => 'PY-101',
            'name'      => 'Python Basics',
        ]);
    }

    public function test_super_admin_can_view_update_and_delete_any_formation(): void
    {
        // View
        $response = $this->actingAs($this->superAdmin)->get(route('formations.show', $this->formationMunich));
        $response->assertOk();
        $response->assertSee('English B2 Munich');

        // Update
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('formations.update', $this->formationMunich), [
            'name'           => 'English B2 Advanced Munich',
            'code'           => 'EN-B2',
            'duration_hours' => 110,
            'duration_weeks' => 7,
            'price'          => 550.00,
            'currency'       => 'EUR',
            'status'         => 'active',
        ]);
        $updateResponse->assertRedirect(route('formations.show', $this->formationMunich));
        $this->assertDatabaseHas('formations', ['name' => 'English B2 Advanced Munich']);

        // Delete
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('formations.destroy', $this->formationMunich));
        $deleteResponse->assertRedirect(route('formations.index'));
        $this->assertDatabaseMissing('formations', ['id' => $this->formationMunich->id]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Admin Berlin & Admin Munich (Multi-Center Isolation)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_center_admin_can_only_view_formations_of_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('formations.index'));
        $response->assertOk();
        $response->assertSee('Deutsch A1 Berlin');
        $response->assertDontSee('English B2 Munich');
    }

    public function test_center_admin_cannot_consult_formation_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('formations.show', $this->formationMunich));
        $response->assertForbidden();
    }

    public function test_center_admin_cannot_edit_update_or_delete_formation_of_another_center(): void
    {
        // Edit
        $editResponse = $this->actingAs($this->adminBerlin)->get(route('formations.edit', $this->formationMunich));
        $editResponse->assertForbidden();

        // Update
        $updateResponse = $this->actingAs($this->adminBerlin)->put(route('formations.update', $this->formationMunich), [
            'name'   => 'Hacked Formation',
            'code'   => 'HACK',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();
        $this->assertDatabaseMissing('formations', ['name' => 'Hacked Formation']);

        // Delete
        $deleteResponse = $this->actingAs($this->adminBerlin)->delete(route('formations.destroy', $this->formationMunich));
        $deleteResponse->assertForbidden();
        $this->assertDatabaseHas('formations', ['id' => $this->formationMunich->id]);
    }

    public function test_center_admin_can_create_formation_in_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('formations.store'), [
            'domain'         => 'informatique',
            'name'           => 'Deutsch B2 Berlin',
            'code'           => 'DE-B2',
            'duration_hours' => 150,
            'duration_weeks' => 10,
            'price'          => 600.00,
            'currency'       => 'EUR',
            'status'         => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('formations', [
            'center_id' => $this->centerBerlin->id,
            'domain'    => 'informatique',
            'code'      => 'DE-B2',
            'name'      => 'Deutsch B2 Berlin',
        ]);
    }

    public function test_center_admin_cannot_spoof_center_id_in_store(): void
    {
        // Admin Berlin tente de spécifier center_id de Munich
        $response = $this->actingAs($this->adminBerlin)->post(route('formations.store'), [
            'center_id' => $this->centerMunich->id,
            'domain'    => 'informatique',
            'name'      => 'Fraude Munich',
            'code'      => 'FRAUD-1',
            'status'    => 'active',
        ]);

        $response->assertRedirect();
        // Le serveur a forcé le center_id à Berlin
        $this->assertDatabaseHas('formations', [
            'center_id' => $this->centerBerlin->id,
            'code'      => 'FRAUD-1',
        ]);
        $this->assertDatabaseMissing('formations', [
            'center_id' => $this->centerMunich->id,
            'code'      => 'FRAUD-1',
        ]);
    }

    public function test_admin_munich_cannot_view_or_modify_berlin_formation(): void
    {
        $response = $this->actingAs($this->adminMunich)->get(route('formations.show', $this->formationBerlin));
        $response->assertForbidden();

        $updateResponse = $this->actingAs($this->adminMunich)->put(route('formations.update', $this->formationBerlin), [
            'name'   => 'Munich take over',
            'code'   => 'DE-A1',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Unicité Scoped du Code (center_id, code)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_same_code_in_same_center_is_rejected(): void
    {
        // 'DE-A1' existe déjà à Berlin
        $response = $this->actingAs($this->adminBerlin)->post(route('formations.store'), [
            'domain' => 'informatique',
            'name'   => 'Autre cours A1',
            'code'   => 'DE-A1',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_same_code_in_different_center_is_allowed(): void
    {
        // 'DE-A1' existe déjà à Berlin. Munich veut aussi créer 'DE-A1'
        $response = $this->actingAs($this->adminMunich)->post(route('formations.store'), [
            'domain' => 'informatique',
            'name'   => 'Deutsch A1 Munich',
            'code'   => 'DE-A1',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('formations', [
            'center_id' => $this->centerMunich->id,
            'code'      => 'DE-A1',
            'name'      => 'Deutsch A1 Munich',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Role Permissions: Teacher & Student Access Denied (403)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_teacher_is_forbidden_from_formation_management(): void
    {
        $response = $this->actingAs($this->teacherBerlin)->get(route('formations.index'));
        $response->assertForbidden();

        $createResponse = $this->actingAs($this->teacherBerlin)->get(route('formations.create'));
        $createResponse->assertForbidden();

        $showResponse = $this->actingAs($this->teacherBerlin)->get(route('formations.show', $this->formationBerlin));
        $showResponse->assertForbidden();
    }

    public function test_student_is_forbidden_from_formation_management(): void
    {
        $response = $this->actingAs($this->studentBerlin)->get(route('formations.index'));
        $response->assertForbidden();

        $showResponse = $this->actingAs($this->studentBerlin)->get(route('formations.show', $this->formationBerlin));
        $showResponse->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Validation & Toggle Status
    // ──────────────────────────────────────────────────────────────────────────

    public function test_validation_requires_mandatory_fields_and_positive_numbers(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('formations.store'), [
            'name'           => '',
            'code'           => '',
            'price'          => -10,
            'duration_hours' => -5,
            'duration_weeks' => -2,
            'status'         => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['domain', 'name', 'code', 'price', 'duration_hours', 'duration_weeks', 'status']);
    }

    public function test_admin_can_toggle_formation_status(): void
    {
        $this->assertTrue($this->formationBerlin->isActive());

        $response = $this->actingAs($this->adminBerlin)->patch(route('formations.toggle-status', $this->formationBerlin));
        $response->assertRedirect();

        $this->formationBerlin->refresh();
        $this->assertTrue($this->formationBerlin->isInactive());
    }

    public function test_admin_cannot_toggle_formation_status_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->patch(route('formations.toggle-status', $this->formationMunich));
        $response->assertForbidden();
    }
}
