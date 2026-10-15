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

    /** MET Norway の天気予報の、偽物の返事（時刻は UTC → 記号） */
    private function fakeMet(array $symbols): void
    {
        $timeseries = [];
        foreach ($symbols as $time => $symbol) {
            $timeseries[] = ['time' => $time, 'data' => ['next_1_hours' => ['summary' => ['symbol_code' => $symbol]]]];
        }
        Http::fake(['*api.met.no/*' => Http::response(['properties' => ['timeseries' => $timeseries]])]);
    }

    /** 日本時間のある日の正午を、UTC の時刻の文字列にする */
    private function utcNoonOf(\Illuminate\Support\Carbon $day): string
    {
        return $day->copy()->timezone('Asia/Tokyo')->setTime(12, 0)->utc()->format('Y-m-d\TH:i:s\Z');
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

    public function test_met_symbols_become_four_labels(): void
    {
        // MET Norway の記号（#146）。昼・夜の印が付いていても読める
        $this->assertSame('晴れ', WeatherService::labelForSymbol('clearsky_day'));
        $this->assertSame('晴れ', WeatherService::labelForSymbol('fair_night'));
        $this->assertSame('曇り', WeatherService::labelForSymbol('partlycloudy_polartwilight'));
        $this->assertSame('曇り', WeatherService::labelForSymbol('fog'));
        $this->assertSame('雨', WeatherService::labelForSymbol('heavyrainshowers_night'));
        $this->assertSame('雨', WeatherService::labelForSymbol('lightsleet'));
        $this->assertSame('雨', WeatherService::labelForSymbol('rainandthunder'));
        $this->assertSame('雪', WeatherService::labelForSymbol('snowshowersandthunder_day'));
        $this->assertNull(WeatherService::labelForSymbol(null));
    }

    public function test_karte_shows_forecast_and_caches_it(): void
    {
        $me = User::factory()->create();
        $spot = $this->spotWithLocation($me);
        $this->fakeMet([$this->utcNoonOf(now()) => 'cloudy']);

        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertSee('天気予報')
            ->assertSee('曇り')
            ->assertSee('MET Norway');

        // MET Norway の決まりで、アプリの名前と連絡先を名乗る。位置は小数第2位まで
        Http::assertSent(fn($request) => str_contains($request->url(), 'api.met.no')
            && str_starts_with($request->header('User-Agent')[0] ?? '', 'FishingLog/')
            && $request['lat'] == 39.72 && $request['lon'] == 140.10);

        // 2回目は覚えておいた予報を使い、問い合わせない
        $this->actingAs($me)->get("/spots/{$spot->id}");
        Http::assertSentCount(1);
    }

    public function test_forecast_is_the_worst_weather_of_the_day(): void
    {
        // 同じ日に晴れと雨があれば「雨」（#146）
        $me = User::factory()->create();
        $spot = $this->spotWithLocation($me);
        $today = now('Asia/Tokyo')->startOfDay();
        $this->fakeMet([
            $today->copy()->setTime(9, 0)->utc()->format('Y-m-d\TH:i:s\Z') => 'clearsky_day',
            $today->copy()->setTime(15, 0)->utc()->format('Y-m-d\TH:i:s\Z') => 'lightrain',
            $today->copy()->setTime(18, 0)->utc()->format('Y-m-d\TH:i:s\Z') => 'partlycloudy_night',
        ]);

        $this->actingAs($me)->get("/spots/{$spot->id}")->assertSeeText('雨');
    }

    public function test_forecast_days_are_counted_in_japan_time(): void
    {
        // UTC の 15 時は、日本では次の日の 0 時。今日の予報に入れない
        $me = User::factory()->create();
        $spot = $this->spotWithLocation($me);
        $tomorrow = now('Asia/Tokyo')->addDay()->startOfDay();
        $this->fakeMet([
            $this->utcNoonOf(now()) => 'clearsky_day',
            $tomorrow->copy()->utc()->format('Y-m-d\TH:i:s\Z') => 'heavysnow',
        ]);

        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertSeeText('晴れ')
            ->assertDontSeeText('雪');

        $this->actingAs($me)->get("/spots/{$spot->id}?date={$tomorrow->format('Y-m-d')}")
            ->assertSeeText('雪');
    }

    public function test_karte_explains_when_the_forecast_service_refuses(): void
    {
        // 天気のサービスに断られたときは、理由と、開き直すことを出す（#144）
        $me = User::factory()->create();
        $spot = $this->spotWithLocation($me);
        Http::fake(['*api.met.no/*' => Http::response(['error' => true], 429)]);

        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertOk()
            ->assertSeeText('今は取得できません')
            ->assertSeeText('時間をおいて開き直してください')
            ->assertDontSeeText('取得できませんでした');
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

        // 予報が出るのは今日を入れて 9 日分（8 日先まで。#146）
        $date = now('Asia/Tokyo')->addDays(9)->format('Y-m-d');
        $this->actingAs($me)->get("/spots/{$spot->id}?date={$date}")
            ->assertDontSee('MET Norway')
            ->assertDontSeeText('今は取得できません');

        Http::assertNothingSent();
    }
}
