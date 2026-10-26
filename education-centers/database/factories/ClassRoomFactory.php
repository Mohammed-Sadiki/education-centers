<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\ClassRoom;
use App\Models\Formation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassRoom>
 */
class ClassRoomFactory extends Factory
{
    protected $model = ClassRoom::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'formation_id' => fn (array $attributes) => Formation::factory()
                ->create(['center_id' => (int) $attributes['center_id']])
                ->id,
            'name' => 'Groupe '.fake()->randomElement(['A', 'B', 'C', 'D']).' '.fake()->word(),
            'code' => strtoupper(fake()->unique()->bothify('CLS-####-??')),
            'description' => fake()->optional()->sentence(12),
            'capacity' => fake()->numberBetween(12, 30),
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ];
    }

    public function forFormation(Formation $formation): static
    {
        return $this->state(fn (array $attributes) => [
            'center_id' => $formation->center_id,
            'formation_id' => $formation->id,
            'name' => 'Groupe '.$formation->name,
        ]);
    }

    public function forCenter(Center|int $center): static
    {
        $centerId = $center instanceof Center ? $center->id : $center;

        return $this->state(fn (array $attributes) => [
            'center_id' => $centerId,
            'formation_id' => Formation::factory()->forCenter($centerId),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
