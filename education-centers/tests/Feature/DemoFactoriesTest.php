<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_create_center_coherent_records(): void
    {
        $center = Center::factory()->create();
        $formation = Formation::factory()->forCenter($center)->forDomain('informatique')->create();
        $classRoom = ClassRoom::factory()->forFormation($formation)->create();
        $teacher = Teacher::factory()->forCenter($center)->forDomain('informatique')->create();
        $student = Student::factory()->forCenter($center)->create();
        $enrollment = Enrollment::factory()
            ->forStudent($student)
            ->forClass($classRoom)
            ->accepted()
            ->create();

        $this->assertSame($center->id, $formation->center_id);
        $this->assertSame($center->id, $classRoom->center_id);
        $this->assertSame($formation->id, $classRoom->formation_id);
        $this->assertSame($center->id, $teacher->center_id);
        $this->assertSame($center->id, $student->center_id);
        $this->assertSame($center->id, $student->user->center_id);
        $this->assertSame(User::ROLE_STUDENT, $student->user->role);
        $this->assertSame(User::ROLE_TEACHER, $teacher->user->role);
        $this->assertSame($center->id, $enrollment->center_id);
        $this->assertSame($student->id, $enrollment->student_id);
        $this->assertSame($formation->id, $enrollment->formation_id);
        $this->assertSame($classRoom->id, $enrollment->class_id);
        $this->assertSame(Enrollment::STATUS_ACCEPTED, $enrollment->status);
    }

    public function test_default_factories_keep_related_centers_aligned(): void
    {
        $classRoom = ClassRoom::factory()->create();
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->create();

        $this->assertSame($classRoom->formation->center_id, $classRoom->center_id);
        $this->assertSame($student->center_id, $student->user->center_id);
        $this->assertSame($teacher->center_id, $teacher->user->center_id);
    }
}
