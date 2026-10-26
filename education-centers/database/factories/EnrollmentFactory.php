<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'student_id' => fn (array $attributes) => Student::factory()
                ->forCenter((int) $attributes['center_id'])
                ->create()
                ->id,
            'formation_id' => fn (array $attributes) => Formation::factory()
                ->create(['center_id' => (int) $attributes['center_id']])
                ->id,
            'class_id' => null,
            'registration_number' => strtoupper(fake()->unique()->bothify('INS-######-??')),
            'school_year' => '2026/2027',
            'application_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'status' => Enrollment::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(),
            'decision_date' => null,
            'decision_notes' => null,
            'accepted_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function forStudent(Student $student): static
    {
        return $this->state(fn (array $attributes) => [
            'center_id' => $student->center_id,
            'student_id' => $student->id,
        ]);
    }

    public function forFormation(Formation $formation): static
    {
        return $this->state(fn (array $attributes) => [
            'center_id' => $formation->center_id,
            'formation_id' => $formation->id,
        ]);
    }

    public function forClass(ClassRoom $class): static
    {
        return $this->state(fn (array $attributes) => [
            'center_id' => $class->center_id,
            'formation_id' => $class->formation_id,
            'class_id' => $class->id,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Enrollment::STATUS_ACCEPTED,
            'decision_date' => now()->toDateString(),
            'decision_notes' => 'Dossier accepté.',
            'accepted_at' => now(),
            'cancelled_at' => null,
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Enrollment::STATUS_UNDER_REVIEW,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Enrollment::STATUS_REJECTED,
            'decision_date' => now()->toDateString(),
            'decision_notes' => 'Dossier refusé.',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Enrollment::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}
