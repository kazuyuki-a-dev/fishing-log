<?php

namespace Database\Factories;

use App\Models\Spot;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends Factory<Spot>
 */
class SpotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => 'テスト釣り場' . fake()->unique()->numberBetween(1, 999),
            'prefecture' => fake()->randomElement(config('prefectures')),
            'latitude' => fake()->latitude(31, 45),
            'longitude' => fake()->longitude(129, 145),
            'visibility' => fake()->randomElement(config('fishing.spot_visibility')),
            'caution_type' => fake()->optional()->randomElement(config('fishing.caution_types')),
            'parking_type' => fake()->optional()->randomElement(config('fishing.parking_types')),
            'toilet_available' => fake()->optional()->randomElement(config('fishing.toilet_available')),
            'convenience_distance_m' => fake()->optional()->numberBetween(100, 5000),
        ];
    }
}
