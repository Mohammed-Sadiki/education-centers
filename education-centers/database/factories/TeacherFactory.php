<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * @var list<string>
     */
    private const DOMAINS = [
        'sante',
        'informatique',
        'hotellerie',
        'maconnerie',
        'design-interieur',
        'design-graphique',
    ];

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
            'teacher_number' => strtoupper(fake()->unique()->bothify('TEA-######')),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'user_id' => fn (array $attributes) => User::factory()
                ->teacher((int) $attributes['center_id'])
                ->create(['name' => $firstName.' '.$lastName])
                ->id,
            'domain' => fake()->randomElement(self::DOMAINS),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-28 years')->format('Y-m-d'),
            'place_of_birth' => fake()->city(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'nationality' => fake()->randomElement(['Allemande', 'Française', 'Marocaine', 'Turque']),
            'address' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'Germany',
            'phone' => fake()->phoneNumber(),
            'specialization' => fake()->sentence(6),
            'qualification' => fake()->randomElement(['Master', 'Diplôme supérieur', 'Brevet professionnel']),
            'monthly_salary' => fake()->randomFloat(2, 4000, 9000),
            'hire_date' => fake()->dateTimeBetween('-10 years', '-1 month')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }

    public function forCenter(Center|int $center): static
    {
        $centerId = $center instanceof Center ? $center->id : $center;

        return $this->state(fn (array $attributes) => [
            'center_id' => $centerId,
            'user_id' => User::factory()->teacher($centerId),
        ]);
    }

    public function forDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
