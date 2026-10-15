<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherService
{
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';
    private const ARCHIVE_URL = 'https://archive-api.open-meteo.com/v1/archive';
    // カルテの天気予報はノルウェー気象局（MET Norway）から取る（公開環境では Open-Meteo に回数の上限で断られるため。#146）
    private const MET_URL = 'https://api.met.no/weatherapi/locationforecast/2.0/compact';
    // MET Norway の決まりで、アプリの名前と連絡先を名乗る
    private const USER_AGENT = 'FishingLog/1.0 (+https://github.com/kazuyuki-a-dev/fishing-log)';
    // 天気の悪さの順（その日でいちばん悪いものを出すため）
    private const SEVERITY = ['晴れ' => 1, '曇り' => 2, '雨' => 3, '雪' => 4];

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
     * MET Norway の天気の記号（例：lightrain_night）を、アプリの4つの天候に直す（#146）
     */
    public static function labelForSymbol(?string $symbol): ?string
    {
        if ($symbol === null) {
            return null;
        }

        // 昼・夜などの印（_day・_night・_polartwilight）を外す
        $symbol = preg_replace('/_(day|night|polartwilight)$/', '', $symbol);

        return match (true) {
            str_contains($symbol, 'snow') => '雪',
            str_contains($symbol, 'rain'), str_contains($symbol, 'sleet'), str_contains($symbol, 'thunder') => '雨',  // みぞれ・雷も雨
            in_array($symbol, ['clearsky', 'fair'], true) => '晴れ',
            in_array($symbol, ['partlycloudy', 'cloudy', 'fog'], true) => '曇り',  // 晴れ時々曇り・霧は曇り（labelFor と同じ考え）
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
     * 天気予報を出せる日か（今日から 8 日先まで＝今日を入れて 9 日分）
     * MET Norway の予報は約 9 日先までで、最後の日は途中までしかないため（#146）
     */
    public function isForecastable(Carbon $date): bool
    {
        $date = $date->copy()->timezone('Asia/Tokyo')->startOfDay();
        $today = now('Asia/Tokyo')->startOfDay();

        return $date->gte($today) && $date->lte($today->copy()->addDays(8));
    }

    /**
     * 選んだ日の天気予報（取れなければ null）（FN-11）
     * 1回の問い合わせで全部の日の予報が来るので、場所ごとに「日付 → 天気」を 1 時間覚えておく
     */
    public function forecastFor(float $lat, float $lng, Carbon $date): ?string
    {
        $day = $date->copy()->timezone('Asia/Tokyo')->toDateString();

        // 失敗したときは null なので覚えない（次に開いたときに、また問い合わせる）
        $byDay = Cache::remember(sprintf('forecast:met:%.2f:%.2f', $lat, $lng), now()->addHour(), function () use ($lat, $lng) {
            $timeseries = $this->request(self::MET_URL, [
                // 小数第2位（約1km）にまるめる。近くの場所はまとめて1回で済む
                'lat' => round($lat, 2),
                'lon' => round($lng, 2),
            ], ['User-Agent' => self::USER_AGENT])['properties']['timeseries'] ?? null;

            return $timeseries ? self::worstByDay($timeseries) : null;
        });

        return $byDay[$day] ?? null;
    }

    /**
     * 時刻ごとの予報を日本時間の日付に分け、その日でいちばん悪い天気を選ぶ
     * （Open-Meteo の1日の天気も「その日でいちばん強い天気」なので、それにそろえる）
     */
    private static function worstByDay(array $timeseries): array
    {
        $byDay = [];
        foreach ($timeseries as $entry) {
            // 先の日は 6 時間ごとになり、1時間の予報がない
            $symbol = $entry['data']['next_1_hours']['summary']['symbol_code']
                ?? $entry['data']['next_6_hours']['summary']['symbol_code']
                ?? null;
            $label = self::labelForSymbol($symbol);
            if ($label === null) {
                continue;
            }

            // 時刻は世界標準時（UTC）。日本時間の日付に直す（UTC の 15 時は、日本の次の日の 0 時）
            $day = Carbon::parse($entry['time'])->timezone('Asia/Tokyo')->toDateString();
            if (! isset($byDay[$day]) || self::SEVERITY[$label] > self::SEVERITY[$byDay[$day]]) {
                $byDay[$day] = $label;
            }
        }

        return $byDay;
    }

    /**
     * 外のサービスに問い合わせる。5 秒で返事がないときや、失敗したときは空を返す
     */
    private function request(string $url, array $query, array $headers = []): array
    {
        try {
            $response = Http::timeout(5)->withHeaders($headers)->get($url, $query);
            if (! $response->successful()) {
                // 断られたときは、理由をログに残す（公開環境で原因を調べるため。#140）
                Log::warning('天気のサービスに断られました', ['url' => $url, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 200)]);

                return [];
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            // 返事がない・つながらないときも、理由をログに残す（#140）
            Log::warning('天気のサービスにつながりませんでした', ['url' => $url, 'error' => $e->getMessage()]);

            return [];
        }
    }
}
