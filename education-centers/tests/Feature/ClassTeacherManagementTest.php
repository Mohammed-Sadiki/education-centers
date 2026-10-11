<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Formation;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassTeacherManagementTest extends TestCase
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
    protected Teacher $inactiveTeacherBerlin;
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

        // 2. Administrateurs
        $this->superAdmin = User::factory()->superAdmin()->create([
            'email'  => 'superadmin@test.de',
            'status' => 'active',
        ]);

        $this->adminBerlin = User::factory()->centerAdmin($this->centerBerlin->id)->create([
            'email'  => 'admin.berlin@test.de',
            'status' => 'active',
        ]);

        $this->adminMunich = User::factory()->centerAdmin($this->centerMunich->id)->create([
            'email'  => 'admin.munich@test.de',
            'status' => 'active',
        ]);

        // 3. Professeurs et Élèves
        $this->teacherUserBerlin = User::factory()->teacher($this->centerBerlin->id)->create([
            'email'  => 'prof.berlin@test.de',
            'status' => 'active',
        ]);

        $this->teacherUserMunich = User::factory()->teacher($this->centerMunich->id)->create([
            'email'  => 'prof.munich@test.de',
            'status' => 'active',
        ]);

        $this->studentUserBerlin = User::factory()->student($this->centerBerlin->id)->create([
            'email'  => 'student.berlin@test.de',
            'status' => 'active',
        ]);

        // 4. Dossiers Professeurs
        $this->teacherBerlin = Teacher::create([
            'user_id'        => $this->teacherUserBerlin->id,
            'center_id'      => $this->centerBerlin->id,
            'teacher_number' => 'BER-T-000001',
            'first_name'     => 'Johannes',
            'last_name'      => 'Kohl',
            'status'         => 'active',
        ]);

        $this->teacherMunich = Teacher::create([
            'user_id'        => $this->teacherUserMunich->id,
            'center_id'      => $this->centerMunich->id,
            'teacher_number' => 'MUN-T-000001',
            'first_name'     => 'Greta',
            'last_name'      => 'Schneider',
            'status'         => 'active',
        ]);

        $inactiveUser = User::factory()->teacher($this->centerBerlin->id)->create([
            'email' => 'inactive.prof@test.de',
        ]);

        $this->inactiveTeacherBerlin = Teacher::create([
            'user_id'        => $inactiveUser->id,
            'center_id'      => $this->centerBerlin->id,
            'teacher_number' => 'BER-T-000002',
            'first_name'     => 'Inactif',
            'last_name'      => 'Prof',
            'status'         => 'inactive',
        ]);

        // 5. Formations
        $this->formationBerlin = Formation::create([
            'center_id' => $this->centerBerlin->id,
            'name'      => 'Deutsch A1 Berlin',
            'code'      => 'DE-A1',
            'status'    => 'active',
        ]);

        $this->formationMunich = Formation::create([
            'center_id' => $this->centerMunich->id,
            'name'      => 'English B2 Munich',
            'code'      => 'EN-B2',
            'status'    => 'active',
        ]);

        // 6. Classes
        $this->classBerlin = ClassRoom::create([
            'center_id'    => $this->centerBerlin->id,
            'formation_id' => $this->formationBerlin->id,
            'name'         => 'Classe Berlin A1-01',
            'code'         => 'A1-01',
            'status'       => 'active',
        ]);

        $this->classMunich = ClassRoom::create([
            'center_id'    => $this->centerMunich->id,
            'formation_id' => $this->formationMunich->id,
            'name'         => 'Classe Munich B2-01',
            'code'         => 'B2-01',
            'status'       => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Tests d'Affectation & Retrait (Super Admin)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_assign_teacher_to_class(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => $this->teacherBerlin->id]
        );

        $response->assertRedirect(route('classes.show', $this->classBerlin));
        $this->assertDatabaseHas('class_teacher', [
            'class_id'   => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
        ]);
        $this->assertTrue($this->classBerlin->hasTeacher($this->teacherBerlin));
    }

    public function test_super_admin_can_unassign_teacher_from_class(): void
    {
        $this->classBerlin->teachers()->attach($this->teacherBerlin->id);
        $this->assertDatabaseHas('class_teacher', [
            'class_id'   => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(
            route('classes.teachers.destroy', [$this->classBerlin, $this->teacherBerlin])
        );

        $response->assertRedirect(route('classes.show', $this->classBerlin));
        $this->assertDatabaseMissing('class_teacher', [
            'class_id'   => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
        ]);
        // Vérifier que le modèle Teacher n'a pas été supprimé
        $this->assertDatabaseHas('teachers', ['id' => $this->teacherBerlin->id]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Tests Admin Berlin & Admin Munich (Multi-Centres Isolation)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_berlin_can_assign_teacher_of_berlin_to_berlin_class(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => $this->teacherBerlin->id]
        );

        $response->assertRedirect(route('classes.show', $this->classBerlin));
        $this->assertDatabaseHas('class_teacher', [
            'class_id'   => $this->classBerlin->id,
            'teacher_id' => $this->teacherBerlin->id,
        ]);
    }

    public function test_admin_munich_can_assign_teacher_of_munich_to_munich_class(): void
    {
        $response = $this->actingAs($this->adminMunich)->post(
            route('classes.teachers.store', $this->classMunich),
            ['teacher_id' => $this->teacherMunich->id]
        );

        $response->assertRedirect(route('classes.show', $this->classMunich));
        $this->assertDatabaseHas('class_teacher', [
            'class_id'   => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
        ]);
    }

    public function test_admin_berlin_cannot_assign_munich_teacher_to_berlin_class(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => $this->teacherMunich->id]
        );

        $response->assertSessionHasErrors('teacher_id');
        $this->assertDatabaseMissing('class_teacher', [
            'class_id'   => $this->classBerlin->id,
            'teacher_id' => $this->teacherMunich->id,
        ]);
    }

    public function test_admin_berlin_cannot_assign_berlin_teacher_to_munich_class(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(
            route('classes.teachers.store', $this->classMunich),
            ['teacher_id' => $this->teacherBerlin->id]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('class_teacher', [
            'class_id'   => $this->classMunich->id,
            'teacher_id' => $this->teacherBerlin->id,
        ]);
    }

    public function test_admin_centre_cannot_unassign_teacher_from_another_center_class(): void
    {
        $this->classMunich->teachers()->attach($this->teacherMunich->id);

        // Admin Berlin tente de retirer un professeur de la classe de Munich
        $response = $this->actingAs($this->adminBerlin)->delete(
            route('classes.teachers.destroy', [$this->classMunich, $this->teacherMunich])
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('class_teacher', [
            'class_id'   => $this->classMunich->id,
            'teacher_id' => $this->teacherMunich->id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Role Permissions (Teacher & Student Denied - 403)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_teacher_is_forbidden_from_managing_assignments(): void
    {
        $response = $this->actingAs($this->teacherUserBerlin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => $this->teacherBerlin->id]
        );
        $response->assertForbidden();

        $deleteResponse = $this->actingAs($this->teacherUserBerlin)->delete(
            route('classes.teachers.destroy', [$this->classBerlin, $this->teacherBerlin])
        );
        $deleteResponse->assertForbidden();
    }

    public function test_student_is_forbidden_from_managing_assignments(): void
    {
        $response = $this->actingAs($this->studentUserBerlin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => $this->teacherBerlin->id]
        );
        $response->assertForbidden();

        $deleteResponse = $this->actingAs($this->studentUserBerlin)->delete(
            route('classes.teachers.destroy', [$this->classBerlin, $this->teacherBerlin])
        );
        $deleteResponse->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Validations & Contraintes Métier
    // ──────────────────────────────────────────────────────────────────────────

    public function test_cannot_assign_duplicate_teacher_to_same_class(): void
    {
        $this->classBerlin->teachers()->attach($this->teacherBerlin->id);

        $response = $this->actingAs($this->adminBerlin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => $this->teacherBerlin->id]
        );

        $response->assertSessionHasErrors('teacher_id');
        $this->assertCount(1, $this->classBerlin->fresh()->teachers);
    }

    public function test_cannot_assign_non_existent_teacher(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => 99999]
        );

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_cannot_assign_inactive_teacher(): void
    {
        $response = $this->actingAs($this->adminBerlin)->post(
            route('classes.teachers.store', $this->classBerlin),
            ['teacher_id' => $this->inactiveTeacherBerlin->id]
        );

        $response->assertSessionHasErrors('teacher_id');
        $this->assertDatabaseMissing('class_teacher', [
            'class_id'   => $this->classBerlin->id,
            'teacher_id' => $this->inactiveTeacherBerlin->id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Relations Eloquent Bidirectionnelles
    // ──────────────────────────────────────────────────────────────────────────

    public function test_eloquent_bidirectional_relationships_work_correctly(): void
    {
        $this->classBerlin->teachers()->attach($this->teacherBerlin->id);

        $this->assertTrue($this->classBerlin->teachers->contains($this->teacherBerlin));
        $this->assertTrue($this->teacherBerlin->classes->contains($this->classBerlin));
    }
}
