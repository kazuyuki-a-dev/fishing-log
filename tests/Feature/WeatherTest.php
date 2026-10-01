<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    use RefreshDatabase;

    /** 位置のある自分の釣り場 */
    private function spotWithLocation(User $user): Spot
    {
        return Spot::factory()->create(['created_by' => $user->id, 'latitude' => 39.72, 'longitude' => 140.10]);
    }

    private function recordTrip(User $user, Spot $spot, string $wentAt, ?string $weather = null)
    {
        return $this->actingAs($user)->post('/trips', [
            'spot_id' => $spot->id,
            'went_at' => $wentAt,
            'time_of_day' => '朝マズメ',
            'visibility' => 'private',
            'weather' => $weather,
        ]);
    }

    /** 1時間ごとの天気の、偽物の返事 */
    private function fakeHourly(string $hour, int $code): void
    {
        Http::fake(['*open-meteo.com/*' => Http::response([
            'hourly' => ['time' => [$hour], 'weather_code' => [$code]],
        ])]);
    }

    public function test_weather_codes_become_four_labels(): void
    {
        $this->assertSame('晴れ', WeatherService::labelFor(0));
        $this->assertSame('曇り', WeatherService::labelFor(3));
        $this->assertSame('曇り', WeatherService::labelFor(45));   // 霧
        $this->assertSame('雨', WeatherService::labelFor(61));
        $this->assertSame('雨', WeatherService::labelFor(95));     // 雷雨
        $this->assertSame('雪', WeatherService::labelFor(73));
        $this->assertNull(WeatherService::labelFor(null));
    }

    public function test_empty_weather_is_filled_automatically(): void
    {
        $me = User::factory()->create();
        $at = now('Asia/Tokyo')->subDays(3)->setTime(6, 0);
        $this->fakeHourly($at->format('Y-m-d\TH:00'), 61);

        $this->recordTrip($me, $this->spotWithLocation($me), $at->format('Y-m-d\TH:i'));

        $this->assertSame('雨', Trip::firstOrFail()->weather);
    }

    public function test_chosen_weather_is_kept_and_not_requested(): void
    {
        $me = User::factory()->create();
        $at = now('Asia/Tokyo')->subDays(3)->setTime(6, 0);
        $this->fakeHourly($at->format('Y-m-d\TH:00'), 61);

        $this->recordTrip($me, $this->spotWithLocation($me), $at->format('Y-m-d\TH:i'), '晴れ');

        $this->assertSame('晴れ', Trip::firstOrFail()->weather);
        Http::assertNothingSent();
    }

    public function test_spot_without_location_is_not_requested(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id, 'latitude' => null, 'longitude' => null]);
        Http::fake();

        $this->recordTrip($me, $spot, now('Asia/Tokyo')->subDays(3)->format('Y-m-d\TH:i'));

        $this->assertNull(Trip::firstOrFail()->weather);
        Http::assertNothingSent();
    }

    public function test_trip_is_saved_even_if_the_weather_service_fails(): void
    {
        $me = User::factory()->create();
        Http::fake(['*open-meteo.com/*' => Http::response([], 500)]);

        $this->recordTrip($me, $this->spotWithLocation($me), now('Asia/Tokyo')->subDays(3)->format('Y-m-d\TH:i'))
            ->assertRedirect();

        $this->assertNull(Trip::firstOrFail()->weather);
    }

    public function test_old_trip_uses_the_historical_api(): void
    {
        $me = User::factory()->create();
        $at = now('Asia/Tokyo')->subYear()->setTime(6, 0);
        $this->fakeHourly($at->format('Y-m-d\TH:00'), 0);

        $this->recordTrip($me, $this->spotWithLocation($me), $at->format('Y-m-d\TH:i'));

        $this->assertSame('晴れ', Trip::firstOrFail()->weather);
        Http::assertSent(fn($request) => str_contains($request->url(), 'archive-api.open-meteo.com'));
    }

    public function test_karte_shows_forecast_and_caches_it(): void
    {
        $me = User::factory()->create();
        $spot = $this->spotWithLocation($me);
        Http::fake(['*open-meteo.com/*' => Http::response(['daily' => ['weather_code' => [3]]])]);

        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertSee('天気予報')
            ->assertSee('曇り')
            ->assertSee('Open-Meteo');

        // 2回目は覚えておいた予報を使い、問い合わせない
        $this->actingAs($me)->get("/spots/{$spot->id}");
        Http::assertSentCount(1);
    }

    public function test_karte_without_location_explains_why_there_is_no_forecast(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id, 'latitude' => null, 'longitude' => null]);
        Http::fake();

        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertSee('天気予報は出せません');

        Http::assertNothingSent();
    }

    public function test_no_forecast_for_dates_too_far_ahead(): void
    {
        $me = User::factory()->create();
        $spot = $this->spotWithLocation($me);
        Http::fake();

        $date = now('Asia/Tokyo')->addDays(20)->format('Y-m-d');
        $this->actingAs($me)->get("/spots/{$spot->id}?date={$date}")
            ->assertDontSee('Open-Meteo');

        Http::assertNothingSent();
    }
}
