<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — Isolation par domaine (Super Admin).
 *
 * Le Super Admin navigue domaine par domaine. Quand un domaine est actif
 * (session `active_domain`), seules les données rattachées à ce domaine
 * doivent être visibles : les données d'un domaine sont absentes de l'autre.
 *
 * On vérifie le modèle "relation-dérivé" : la classe et l'inscription héritent
 * du domaine de leur formation, l'étudiant hérite via ses inscriptions.
 */
class DomainIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Formation $sante;

    private Formation $info;

    private ClassRoom $classSante;

    private ClassRoom $classInfo;

    private Student $studentSante;

    private Student $studentInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::create([
            'name' => 'Centre Alpha', 'code' => 'ALPHA', 'address' => 'A', 'status' => 'active',
        ]);

        $this->sante = Formation::create([
            'center_id' => $this->center->id, 'domain' => 'sante',
            'name' => 'Infirmier', 'code' => 'INF-01', 'status' => 'active',
        ]);
        $this->info = Formation::create([
            'center_id' => $this->center->id, 'domain' => 'informatique',
            'name' => 'Développeur', 'code' => 'DEV-01', 'status' => 'active',
        ]);

        $this->classSante = ClassRoom::create([
            'center_id' => $this->center->id, 'formation_id' => $this->sante->id,
            'name' => 'Santé A', 'code' => 'SA-1', 'capacity' => 30, 'status' => 'active',
        ]);
        $this->classInfo = ClassRoom::create([
            'center_id' => $this->center->id, 'formation_id' => $this->info->id,
            'name' => 'Info B', 'code' => 'IB-1', 'capacity' => 30, 'status' => 'active',
        ]);

        $this->studentSante = $this->makeStudent('ST-SANTE', 'Anna');
        $this->studentInfo = $this->makeStudent('ST-INFO', 'Bruno');

        Enrollment::create([
            'center_id' => $this->center->id, 'student_id' => $this->studentSante->id,
            'formation_id' => $this->sante->id, 'class_id' => $this->classSante->id,
            'registration_number' => 'E-1', 'application_date' => '2026-01-01', 'status' => 'accepted',
        ]);
        Enrollment::create([
            'center_id' => $this->center->id, 'student_id' => $this->studentInfo->id,
            'formation_id' => $this->info->id, 'class_id' => $this->classInfo->id,
            'registration_number' => 'E-2', 'application_date' => '2026-01-01', 'status' => 'accepted',
        ]);
    }

    private function makeStudent(string $number, string $first): Student
    {
        $user = User::factory()->create(['role' => 'student', 'center_id' => $this->center->id]);

        return Student::create([
            'user_id' => $user->id, 'center_id' => $this->center->id, 'student_number' => $number,
            'first_name' => $first, 'last_name' => 'X', 'date_of_birth' => '2000-01-01',
            'gender' => 'male', 'status' => 'active',
        ]);
    }

    public function test_sante_domain_shows_only_sante_data(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // Étudiants du domaine santé uniquement.
        $response = $this->actingAs($superAdmin)
            ->withSession(['active_domain' => 'sante'])
            ->get(route('students.index'));

        $students = $response->viewData('students');
        $numbers = collect($students->pluck('student_number'))->all();
        $this->assertContains('ST-SANTE', $numbers);
        $this->assertNotContains('ST-INFO', $numbers);

        // Classes du domaine santé uniquement.
        $classes = $this->actingAs($superAdmin)
            ->withSession(['active_domain' => 'sante'])
            ->get(route('classes.index'))
            ->viewData('classes');
        $codes = collect($classes->pluck('code'))->all();
        $this->assertContains('SA-1', $codes);
        $this->assertNotContains('IB-1', $codes);
    }

    public function test_informatique_domain_hides_sante_data(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $numbers = $this->actingAs($superAdmin)
            ->withSession(['active_domain' => 'informatique'])
            ->get(route('students.index'))
            ->viewData('students')
            ->pluck('student_number')
            ->all();

        $this->assertContains('ST-INFO', $numbers);
        $this->assertNotContains('ST-SANTE', $numbers);
    }

    public function test_no_active_domain_shows_all_data(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $numbers = $this->actingAs($superAdmin)
            ->get(route('students.index'))
            ->viewData('students')
            ->pluck('student_number')
            ->all();

        $this->assertContains('ST-SANTE', $numbers);
        $this->assertContains('ST-INFO', $numbers);
    }

    public function test_domain_dérivé_herite_via_formation(): void
    {
        // Vérification unitaire du modèle relation-dérivé sans HTTP.
        $this->assertSame(
            ['SA-1'],
            ClassRoom::forDomain('sante')->pluck('code')->all(),
        );
        $this->assertSame(
            ['ST-SANTE'],
            Student::forDomain('sante')->pluck('student_number')->all(),
        );
        $this->assertSame(
            ['DEV-01'],
            Formation::forDomain('informatique')->pluck('code')->all(),
        );
    }
}
