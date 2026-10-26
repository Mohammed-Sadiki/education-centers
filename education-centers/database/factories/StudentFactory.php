<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'center_id' => Center::factory(),
            'student_number' => strtoupper(fake()->unique()->bothify('STU-######')),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'user_id' => fn (array $attributes) => User::factory()
                ->student((int) $attributes['center_id'])
                ->create(['name' => $firstName.' '.$lastName])
                ->id,
            'date_of_birth' => fake()->dateTimeBetween('-35 years', '-17 years')->format('Y-m-d'),
            'place_of_birth' => fake()->city(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'nationality' => fake()->randomElement(['Allemande', 'Française', 'Marocaine', 'Turque']),
            'address' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'Germany',
            'phone' => fake()->phoneNumber(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'emergency_contact_relation' => fake()->randomElement(['Parent', 'Conjoint(e)', 'Tuteur']),
            'notes' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }

    public function forCenter(Center|int $center): static
    {
        $centerId = $center instanceof Center ? $center->id : $center;

        return $this->state(fn (array $attributes) => [
            'center_id' => $centerId,
            'user_id' => User::factory()->student($centerId),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
