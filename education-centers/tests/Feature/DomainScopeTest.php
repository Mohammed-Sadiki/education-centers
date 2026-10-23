<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Domain;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Formation;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 — Vérifie le modèle de domaine "relation-dérivé" :
 * le domaine est porté par formations.domain (slug) et hérité par les entités
 * liées via leurs relations, sans colonne domain_id additionnelle.
 */
class DomainScopeTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;
    private Formation $formationSante;
    private Formation $formationInfo;
    private ClassRoom $classSante;
    private ClassRoom $classInfo;
    private Room $room;
    private Teacher $teacherSante;
    private Teacher $teacherInfoViaClass;
    private Student $studentMulti;

    protected function setUp(): void
    {
        parent::setUp();

        Domain::create(['slug' => 'sante', 'name' => 'Santé', 'sort_order' => 1, 'is_active' => true]);
        Domain::create(['slug' => 'informatique', 'name' => 'Informatique', 'sort_order' => 2, 'is_active' => true]);

        $this->center = Center::create(['name' => 'Centre Berlin', 'code' => 'BER', 'address' => 'A', 'status' => 'active']);

        $this->formationSante = Formation::create([
            'center_id' => $this->center->id, 'domain' => 'sante', 'name' => 'Soins infirmiers',
            'code' => 'SAN-01', 'duration_hours' => 200, 'price' => 500, 'currency' => 'EUR', 'status' => 'active',
        ]);
        $this->formationInfo = Formation::create([
            'center_id' => $this->center->id, 'domain' => 'informatique', 'name' => 'Développement web',
            'code' => 'INF-01', 'duration_hours' => 300, 'price' => 600, 'currency' => 'EUR', 'status' => 'active',
        ]);

        $this->classSante = ClassRoom::create([
            'center_id' => $this->center->id, 'formation_id' => $this->formationSante->id,
            'name' => 'Classe Santé', 'code' => 'SAN-C1', 'capacity' => 20, 'status' => 'active',
        ]);
        $this->classInfo = ClassRoom::create([
            'center_id' => $this->center->id, 'formation_id' => $this->formationInfo->id,
            'name' => 'Classe Info', 'code' => 'INF-C1', 'capacity' => 20, 'status' => 'active',
        ]);

        $this->room = Room::create(['center_id' => $this->center->id, 'name' => 'Salle 1', 'code' => 'R1', 'capacity' => 20, 'status' => 'active']);

        // Professeur rattaché au domaine via sa colonne propre.
        $this->teacherSante = $this->makeTeacher('TSAN', 'Claire', 'Dupont', 'sante');
        // Professeur sans colonne domain, rattaché uniquement via sa classe (informatique).
        $this->teacherInfoViaClass = $this->makeTeacher('TINF', 'Marc', 'Martin', null);
        $this->classInfo->teachers()->attach($this->teacherInfoViaClass->id);

        // Étudiant inscrit dans les DEUX domaines.
        $this->studentMulti = $this->makeStudent('STU-M', 'Léa', 'Bernard');
        Enrollment::create([
            'center_id' => $this->center->id, 'student_id' => $this->studentMulti->id,
            'formation_id' => $this->formationSante->id, 'registration_number' => 'BER-INS-1',
            'application_date' => '2026-01-10', 'status' => Enrollment::STATUS_ACCEPTED,
        ]);
        Enrollment::create([
            'center_id' => $this->center->id, 'student_id' => $this->studentMulti->id,
            'formation_id' => $this->formationInfo->id, 'registration_number' => 'BER-INS-2',
            'application_date' => '2026-01-11', 'status' => Enrollment::STATUS_ACCEPTED,
        ]);
    }

    private function makeTeacher(string $number, string $first, string $last, ?string $domain): Teacher
    {
        $user = User::create([
            'name' => "$first $last", 'email' => strtolower($number).'@plateforme.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_TEACHER, 'center_id' => $this->center->id, 'status' => 'active',
        ]);

        return Teacher::create([
            'user_id' => $user->id, 'center_id' => $this->center->id, 'teacher_number' => $number,
            'first_name' => $first, 'last_name' => $last, 'gender' => 'female', 'domain' => $domain, 'status' => 'active',
        ]);
    }

    private function makeStudent(string $number, string $first, string $last): Student
    {
        $user = User::create([
            'name' => "$first $last", 'email' => strtolower($number).'@plateforme.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_STUDENT, 'center_id' => $this->center->id, 'status' => 'active',
        ]);

        return Student::create([
            'user_id' => $user->id, 'center_id' => $this->center->id, 'student_number' => $number,
            'first_name' => $first, 'last_name' => $last, 'date_of_birth' => '2000-01-01', 'gender' => 'female', 'status' => 'active',
        ]);
    }

    public function test_formation_filters_on_its_own_domain_column(): void
    {
        $this->assertEquals(
            [$this->formationSante->id],
            Formation::forDomain('sante')->pluck('id')->all()
        );
        $this->assertEmpty(Formation::forDomain('unknown')->pluck('id')->all());
        // 'all'/null = aucun filtre.
        $this->assertCount(2, Formation::forDomain('all')->get());
    }

    public function test_class_and_schedule_inherit_domain_via_formation(): void
    {
        $this->assertEquals([$this->classSante->id], ClassRoom::forDomain('sante')->pluck('id')->all());
        $this->assertEquals([$this->classInfo->id], ClassRoom::forDomain('informatique')->pluck('id')->all());
        $this->assertSame('sante', $this->classSante->domainSlug());

        $schedule = Schedule::create([
            'center_id' => $this->center->id, 'class_id' => $this->classSante->id,
            'teacher_id' => $this->teacherSante->id, 'room_id' => $this->room->id,
            'date' => '2026-10-25', 'start_time' => '09:00', 'end_time' => '11:00',
            'title' => 'Cours santé', 'type' => 'course', 'status' => 'scheduled',
        ]);
        $this->assertEquals([$schedule->id], Schedule::forDomain('sante')->pluck('id')->all());
        $this->assertEmpty(Schedule::forDomain('informatique')->pluck('id')->all());
    }

    public function test_enrollment_and_evaluation_inherit_domain_via_formation(): void
    {
        $this->assertEquals(
            [$this->formationSante->id],
            Enrollment::forDomain('sante')->pluck('formation_id')->all()
        );

        $evaluation = Evaluation::create([
            'center_id' => $this->center->id, 'class_id' => $this->classInfo->id,
            'formation_id' => $this->formationInfo->id, 'teacher_id' => $this->teacherInfoViaClass->id,
            'title' => 'Examen info', 'type' => 'exam', 'evaluation_date' => '2026-11-01',
            'max_grade' => 20, 'status' => 'published',
        ]);
        $this->assertEquals([$evaluation->id], Evaluation::forDomain('informatique')->pluck('id')->all());
    }

    public function test_student_appears_in_every_domain_of_its_enrollments(): void
    {
        $this->assertEquals([$this->studentMulti->id], Student::forDomain('sante')->pluck('id')->all());
        $this->assertEquals([$this->studentMulti->id], Student::forDomain('informatique')->pluck('id')->all());

        $slugs = $this->studentMulti->domainSlugs()->all();
        sort($slugs);
        $this->assertSame(['informatique', 'sante'], $slugs);
        $this->assertEqualsCanonicalizing(
            ['sante', 'informatique'],
            $this->studentMulti->domains()->pluck('slug')->all()
        );
    }

    public function test_teacher_matches_own_domain_or_domain_of_assigned_classes(): void
    {
        // Via sa colonne propre.
        $this->assertTrue(Teacher::forDomain('sante')->whereKey($this->teacherSante->id)->exists());
        // Via sa classe affectée (informatique), sans colonne domain.
        $this->assertTrue(Teacher::forDomain('informatique')->whereKey($this->teacherInfoViaClass->id)->exists());
        // Le prof santé n'est pas en informatique.
        $this->assertFalse(Teacher::forDomain('informatique')->whereKey($this->teacherSante->id)->exists());
    }

    public function test_domain_relation_round_trip(): void
    {
        $domain = Domain::findBySlug('sante');
        $this->assertNotNull($domain);
        $this->assertEquals([$this->formationSante->id], $domain->formations()->pluck('id')->all());
        $this->assertSame('sante', $this->formationSante->domainModel->slug);
        $this->assertNull(Domain::findBySlug(null));
    }
}
