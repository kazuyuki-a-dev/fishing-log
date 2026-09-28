<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Spot;
use App\Services\TideCalculator;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'spot_id' => Spot::factory(),
            'went_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'time_of_day' => fake()->randomElement(config('fishing.times_of_day')),
            'visibility' => fake()->randomElement(config('fishing.trip_visibility')),
            'tide' => fn(array $attributes) => (new TideCalculator())->tideFor(Carbon::parse($attributes['went_at'])),
            'weather' => fake()->randomElement(config('fishing.weathers')),
        ];
    }
}
