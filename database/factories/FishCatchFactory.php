<?php

namespace Database\Factories;

use App\Models\FishCatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Trip;

/**
 * @extends Factory<FishCatch>
 */
class FishCatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'fish_species' => fake()->randomElement(config('fishing.fish_species')),
            'method' => fake()->randomElement(config('fishing.methods')),
            'length_cm' => fake()->randomFloat(1, 10, 80),
            'weight_g' => fake()->optional()->numberBetween(50, 5000),
        ];
    }
}
