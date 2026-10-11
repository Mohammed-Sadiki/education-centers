<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassManagementTest extends TestCase
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
    protected ClassRoom $classBerlin;
    protected ClassRoom $classMunich;

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

        // 5. Formations
        $this->formationBerlin = Formation::create([
            'center_id'      => $this->centerBerlin->id,
            'name'           => 'Deutsch A1 Berlin',
            'code'           => 'DE-A1',
            'duration_hours' => 120,
            'price'          => 450.00,
            'status'         => 'active',
        ]);

        $this->formationMunich = Formation::create([
            'center_id'      => $this->centerMunich->id,
            'name'           => 'English B2 Munich',
            'code'           => 'EN-B2',
            'duration_hours' => 100,
            'price'          => 500.00,
            'status'         => 'active',
        ]);

        // 6. Classes
        $this->classBerlin = ClassRoom::create([
            'center_id'    => $this->centerBerlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Classe Berlin A1-01',
            'code'         => 'A1-01',
            'capacity'     => 20,
            'start_date'   => '2026-11-01',
            'end_date'     => '2026-12-20',
            'status'       => 'active',
        ]);

        $this->classMunich = ClassRoom::create([
            'center_id'    => $this->centerMunich->id,
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Classe Munich B2-01',
            'code'         => 'B2-01',
            'capacity'     => 15,
            'start_date'   => '2026-11-10',
            'end_date'     => '2027-01-15',
            'status'       => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Super Admin Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_all_classes_and_filter(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('classes.index'));
        $response->assertOk();
        $response->assertSee('Classe Berlin A1-01');
        $response->assertSee('Classe Munich B2-01');

        // Filtrer par centre Berlin
        $filterResponse = $this->actingAs($this->superAdmin)->get(route('classes.index', ['center_id' => $this->centerBerlin->id]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Classe Berlin A1-01');
        $filterResponse->assertDontSee('Classe Munich B2-01');

        // Filtrer par formation
        $formationFilter = $this->actingAs($this->superAdmin)->get(route('classes.index', ['formation_id' => $this->formationBerlin->id]));
        $formationFilter->assertOk();
        $formationFilter->assertSee('Classe Berlin A1-01');
        $formationFilter->assertDontSee('Classe Munich B2-01');
    }

    public function test_super_admin_can_create_class_with_same_center_formation(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('classes.store'), [
            'center_id'    => $this->centerMunich->id,
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Nouvelle Classe Munich',
            'code'         => 'B2-02',
            'capacity'     => 25,
            'start_date'   => '2026-12-01',
            'end_date'     => '2027-02-15',
            'status'       => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classes', [
            'center_id'    => $this->centerMunich->id,
            'formation_id' => $this->formationMunich->id,
            'code'         => 'B2-02',
            'name'         => 'Nouvelle Classe Munich',
        ]);
    }

    public function test_super_admin_can_view_update_and_delete_class(): void
    {
        // View
        $response = $this->actingAs($this->superAdmin)->get(route('classes.show', $this->classMunich));
        $response->assertOk();
        $response->assertSee('Classe Munich B2-01');

        // Update
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('classes.update', $this->classMunich), [
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Classe Munich Updated',
            'code'         => 'B2-01',
            'capacity'     => 18,
            'status'       => 'active',
        ]);
        $updateResponse->assertRedirect(route('classes.show', $this->classMunich));
        $this->assertDatabaseHas('classes', ['name' => 'Classe Munich Updated']);

        // Delete
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('classes.destroy', $this->classMunich));
        $deleteResponse->assertRedirect(route('classes.index'));
        $this->assertDatabaseMissing('classes', ['id' => $this->classMunich->id]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Admin Berlin & Admin Munich (Multi-Center Isolation)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_center_admin_can_only_view_classes_of_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('classes.index'));
        $response->assertOk();
        $response->assertSee('Classe Berlin A1-01');
        $response->assertDontSee('Classe Munich B2-01');
    }

    public function test_center_admin_cannot_consult_class_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->get(route('classes.show', $this->classMunich));
        $response->assertForbidden();
    }

    public function test_center_admin_cannot_edit_update_or_delete_class_of_another_center(): void
    {
        // Edit
        $editResponse = $this->actingAs($this->adminBerlin)->get(route('classes.edit', $this->classMunich));
        $editResponse->assertForbidden();

        // Update
        $updateResponse = $this->actingAs($this->adminBerlin)->put(route('classes.update', $this->classMunich), [
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Hacked Class',
            'code'         => 'HACK',
            'status'       => 'active',
        ]);
        $updateResponse->assertForbidden();
        $this->assertDatabaseMissing('classes', ['name' => 'Hacked Class']);

        // Delete
        $deleteResponse = $this->actingAs($this->adminBerlin)->delete(route('classes.destroy', $this->classMunich));
        $deleteResponse->assertForbidden();
        $this->assertDatabaseHas('classes', ['id' => $this->classMunich->id]);
    }

    public function test_center_admin_can_create_class_in_own_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('classes.store'), [
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Groupe A1-02 Berlin',
            'code'         => 'A1-02',
            'capacity'     => 15,
            'status'       => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classes', [
            'center_id'    => $this->centerBerlin->id,
            'formation_id' => $this->formationBerlin->id,
            'code'         => 'A1-02',
        ]);
    }

    public function test_center_admin_cannot_spoof_center_id_in_store(): void
    {
        // Admin Berlin tente d'injecter center_id de Munich avec la formation de Berlin
        $response = $this->actingAs($this->adminBerlin)->post(route('classes.store'), [
            'center_id'    => $this->centerMunich->id,
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Groupe Spoof',
            'code'         => 'SPOOF-01',
            'status'       => 'active',
        ]);

        $response->assertRedirect();
        // Le serveur a forcé le center_id à Berlin
        $this->assertDatabaseHas('classes', [
            'center_id' => $this->centerBerlin->id,
            'code'      => 'SPOOF-01',
        ]);
        $this->assertDatabaseMissing('classes', [
            'center_id' => $this->centerMunich->id,
            'code'      => 'SPOOF-01',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Cohérence Centre / Formation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_cannot_create_class_with_formation_from_another_center(): void
    {
        // Admin Berlin tente d'utiliser une formation de Munich
        $response = $this->actingAs($this->adminBerlin)->post(route('classes.store'), [
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Incoherent Class',
            'code'         => 'INC-01',
            'status'       => 'active',
        ]);

        $response->assertSessionHasErrors('formation_id');
        $this->assertDatabaseMissing('classes', ['code' => 'INC-01']);
    }

    public function test_super_admin_cannot_associate_mismatched_center_and_formation(): void
    {
        // Super Admin choisit le centre Berlin mais donne la formation de Munich
        $response = $this->actingAs($this->superAdmin)->post(route('classes.store'), [
            'center_id'    => $this->centerBerlin->id,
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Mismatched Class',
            'code'         => 'MISMATCH-01',
            'status'       => 'active',
        ]);

        $response->assertSessionHasErrors('formation_id');
        $this->assertDatabaseMissing('classes', ['code' => 'MISMATCH-01']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Unicité Scoped du Code (center_id, code)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_same_code_in_same_center_is_rejected(): void
    {
        // 'A1-01' existe déjà à Berlin
        $response = $this->actingAs($this->adminBerlin)->post(route('classes.store'), [
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Doublon Code',
            'code'         => 'A1-01',
            'status'       => 'active',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_same_code_in_different_center_is_allowed(): void
    {
        // 'A1-01' existe déjà à Berlin. Munich veut aussi créer 'A1-01'
        $response = $this->actingAs($this->adminMunich)->post(route('classes.store'), [
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Classe Munich A1-01',
            'code'         => 'A1-01',
            'status'       => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classes', [
            'center_id'    => $this->centerMunich->id,
            'formation_id' => $this->formationMunich->id,
            'code'         => 'A1-01',
            'name'         => 'Classe Munich A1-01',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Dates & Capacity Validation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(route('classes.store'), [
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Invalid Dates Class',
            'code'         => 'INV-DATE',
            'start_date'   => '2026-10-20',
            'end_date'     => '2026-10-10',
            'status'       => 'active',
        ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_invalid_capacity_is_rejected(): void
    {
        $response1 = $this->actingAs($this->adminBerlin)->post(route('classes.store'), [
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Zero Capacity',
            'code'         => 'ZERO-CAP',
            'capacity'     => 0,
            'status'       => 'active',
        ]);
        $response1->assertSessionHasErrors('capacity');

        $response2 = $this->actingAs($this->adminBerlin)->post(route('classes.store'), [
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Negative Capacity',
            'code'         => 'NEG-CAP',
            'capacity'     => -5,
            'status'       => 'active',
        ]);
        $response2->assertSessionHasErrors('capacity');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Role Permissions: Teacher & Student Access Denied (403)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_teacher_is_forbidden_from_class_management(): void
    {
        $response = $this->actingAs($this->teacherBerlin)->get(route('classes.index'));
        $response->assertForbidden();

        $createResponse = $this->actingAs($this->teacherBerlin)->get(route('classes.create'));
        $createResponse->assertForbidden();

        $showResponse = $this->actingAs($this->teacherBerlin)->get(route('classes.show', $this->classBerlin));
        $showResponse->assertForbidden();
    }

    public function test_student_is_forbidden_from_class_management(): void
    {
        $response = $this->actingAs($this->studentBerlin)->get(route('classes.index'));
        $response->assertForbidden();

        $showResponse = $this->actingAs($this->studentBerlin)->get(route('classes.show', $this->classBerlin));
        $showResponse->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Toggle Status
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_toggle_class_status(): void
    {
        $this->assertTrue($this->classBerlin->isActive());

        $response = $this->actingAs($this->adminBerlin)->patch(route('classes.toggle-status', $this->classBerlin));
        $response->assertRedirect();

        $this->classBerlin->refresh();
        $this->assertTrue($this->classBerlin->isInactive());
    }

    public function test_admin_cannot_toggle_class_status_of_another_center(): void
    {
        $response = $this->actingAs($this->adminBerlin)->patch(route('classes.toggle-status', $this->classMunich));
        $response->assertForbidden();
    }
}
