<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'ice' => fake()->unique()->numerify('###############'),
            'if_number' => fake()->numerify('########'),
            'rc' => fake()->numerify('######'),
            'address' => fake()->streetAddress(),
            'city' => 'Casablanca',
            'phone' => fake()->numerify('0522 ## ## ##'),
            'email' => fake()->unique()->companyEmail(),
            'contact_name' => fake()->name(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }
}
