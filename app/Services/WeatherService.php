<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class WeatherService
{
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';
    private const ARCHIVE_URL = 'https://archive-api.open-meteo.com/v1/archive';

    /**
     * 天気コード（世界共通の WMO コード）を、アプリの4つの天候に直す
     */
    public static function labelFor(?int $code): ?string
    {
        return match (true) {
            $code === null => null,
            in_array($code, [0, 1], true) => '晴れ',                       // 快晴・ほぼ晴れ
            in_array($code, [2, 3, 45, 48], true) => '曇り',               // 晴れ時々曇り・曇り・霧
            in_array($code, [71, 73, 75, 77, 85, 86], true) => '雪',
            $code >= 51 => '雨',                                            // 霧雨・雨・にわか雨・雷雨
            default => null,
        };
    }

    /**
     * 釣行の日時の天候（取れなければ null）（FN-08）
     */
    public function weatherAt(float $lat, float $lng, Carbon $at): ?string
    {
        $at = $at->copy()->timezone('Asia/Tokyo');
        $date = $at->toDateString();

        // 予報の API は過去 92 日まで取れる。それより前は、過去の天気の API を使う
        $url = $at->lt(now('Asia/Tokyo')->subDays(90)) ? self::ARCHIVE_URL : self::FORECAST_URL;

        $hourly = $this->request($url, [
            'latitude' => $lat,
            'longitude' => $lng,
            'hourly' => 'weather_code',
            'start_date' => $date,
            'end_date' => $date,
            'timezone' => 'Asia/Tokyo',
        ])['hourly'] ?? null;

        if (! $hourly) {
            return null;
        }

        // 1時間ごとの天気から、釣行の時刻（例：06:00）のものを探す
        $index = array_search($at->format('Y-m-d\TH:00'), $hourly['time'] ?? [], true);

        return $index === false ? null : self::labelFor($hourly['weather_code'][$index] ?? null);
    }

    /**
     * 天気予報を出せる日か（今日から 15 日先まで）
     */
    public function isForecastable(Carbon $date): bool
    {
        $date = $date->copy()->timezone('Asia/Tokyo')->startOfDay();
        $today = now('Asia/Tokyo')->startOfDay();

        return $date->gte($today) && $date->lte($today->copy()->addDays(15));
    }

    /**
     * 選んだ日の天気予報（取れなければ null）（FN-11）
     * 同じ場所・同じ日の予報は 1 時間覚えておく
     */
    public function forecastFor(float $lat, float $lng, Carbon $date): ?string
    {
        $day = $date->copy()->timezone('Asia/Tokyo')->toDateString();
        $key = sprintf('forecast:%.2f:%.2f:%s', $lat, $lng, $day);

        return Cache::remember($key, now()->addHour(), function () use ($lat, $lng, $day) {
            $daily = $this->request(self::FORECAST_URL, [
                'latitude' => $lat,
                'longitude' => $lng,
                'daily' => 'weather_code',
                'start_date' => $day,
                'end_date' => $day,
                'timezone' => 'Asia/Tokyo',
            ])['daily'] ?? null;

            return self::labelFor($daily['weather_code'][0] ?? null);
        });
    }

    /**
     * 外のサービスに問い合わせる。5 秒で返事がないときや、失敗したときは空を返す
     */
    private function request(string $url, array $query): array
    {
        try {
            $response = Http::timeout(5)->get($url, $query);

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (Throwable) {
            return [];
        }
    }
}
