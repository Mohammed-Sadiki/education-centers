<?php

namespace Database\Factories;

use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Center>
 */
class CenterFactory extends Factory
{
    protected $model = Center::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->city();

        return [
            'name'        => 'Centre ' . $city,
            'code'        => strtoupper(fake()->unique()->lexify('??????')),
            'address'     => fake()->streetAddress(),
            'city'        => $city,
            'postal_code' => fake()->postcode(),
            'country'     => 'Germany',
            'phone'       => fake()->phoneNumber(),
            'email'       => fake()->safeEmail(),
            'logo'        => null,
            'status'      => 'active',
        ];
    }

    /**
     * État inactif.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
