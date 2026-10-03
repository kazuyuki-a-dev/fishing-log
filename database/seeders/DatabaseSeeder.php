<?php

namespace Database\Seeders;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 開発用のダミーデータ（NF-07）
 * プランナー（FN-16）とカルテ（FN-09）を確かめられるだけの記録を、秋田県に集めて入れる
 * 県の切り替え（FN-17）を確かめるために、神奈川県のデータも少しだけ入れる
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // 時間帯ごとの時刻（何時から何時の間にするか）
    private const HOURS = [
        '朝マズメ' => [4, 6],
        '日中' => [9, 14],
        '夕マズメ' => [16, 18],
        '夜' => [19, 23],
        '終日' => [6, 6],
    ];

    // 小さめの魚（サイズを 12〜30cm にする）。それ以外は 30〜75cm
    private const SMALL_FISH = ['アジ', 'サバ', 'イワシ', 'カマス', 'キス', 'メバル', 'カサゴ', 'カワハギ'];

    // 釣り場ごとによく釣れる魚（釣り場の id => 魚の一覧）
    private array $fishBySpot = [];

    public function run(): void
    {
        // 毎回同じダミーデータになるように、でたらめの元（シード）を固定する
        fake()->seed(2026);

        // ---- 秋田県（メインのデータ） ----

        // 自分でログインして確かめるためのユーザー（パスワードは password）
        $me = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'home_prefecture' => '秋田県',
        ]);

        // ほかの釣り人3人（メインフィールドは同じ県）
        $others = collect([
            ['name' => '港の釣り人', 'email' => 'minato@example.com'],
            ['name' => 'サーフ好き', 'email' => 'surf@example.com'],
            ['name' => '磯の常連', 'email' => 'iso@example.com'],
        ])->map(fn(array $user) => User::factory()->create([...$user, 'home_prefecture' => '秋田県']));

        // 釣り場8件。名前は架空、位置は秋田の海沿いのだいたいの場所
        // 現地の情報（駐車場・トイレ・コンビニ）が空の釣り場は、釣行登録のあとに質問が出る（FN-14）
        $akitaSpots = $this->createSpots('秋田県', [
            [
                'name' => '北港 第一堤防',
                'lat' => 39.7600,
                'lng' => 140.0550,
                'owner' => $me,
                'visibility' => 'public',
                'caution' => 'なし',
                'parking' => '公式駐車場',
                'toilet' => 'あり',
                'convenience' => 800,
                'fish' => ['アジ', 'サバ', 'イワシ']
            ],
            [
                'name' => '南浜サーフ',
                'lat' => 39.6900,
                'lng' => 140.0500,
                'owner' => $others[0],
                'visibility' => 'public',
                'caution' => 'なし',
                'parking' => '路肩等',
                'toilet' => 'なし',
                'convenience' => 1500,
                'fish' => ['ヒラメ', 'キス', 'マゴチ']
            ],
            [
                'name' => '旧河口',
                'lat' => 39.7300,
                'lng' => 140.0600,
                'owner' => $others[1],
                'visibility' => 'public',
                'caution' => '注意あり',
                'parking' => '路肩等',
                'toilet' => '不明',
                'convenience' => null,
                'fish' => ['シーバス', 'クロダイ']
            ],
            [
                'name' => '岬の磯',
                'lat' => 39.8800,
                'lng' => 139.8400,
                'owner' => $others[2],
                'visibility' => 'public',
                'caution' => '立入注意',
                'parking' => '駐車不可',
                'toilet' => 'なし',
                'convenience' => null,
                'fish' => ['メジナ', 'メバル', 'アイナメ']
            ],
            [
                'name' => '東漁港',
                'lat' => 39.3900,
                'lng' => 140.0300,
                'owner' => $others[0],
                'visibility' => 'public',
                'caution' => 'なし',
                'parking' => '公式駐車場',
                'toilet' => 'あり',
                'convenience' => 300,
                'fish' => ['アジ', 'カサゴ', 'アオリイカ']
            ],
            [
                'name' => '西防波堤',
                'lat' => 39.9500,
                'lng' => 139.7100,
                'owner' => $others[1],
                'visibility' => 'public',
                'caution' => '私有地隣接',
                'parking' => null,
                'toilet' => null,
                'convenience' => null,
                'fish' => ['ブリ', 'サワラ', 'タチウオ']
            ],
            [
                'name' => '運河筋',
                'lat' => 39.7450,
                'lng' => 140.0750,
                'owner' => $me,
                'visibility' => 'private',
                'caution' => 'なし',
                'parking' => '路肩等',
                'toilet' => 'なし',
                'convenience' => 600,
                'fish' => ['シーバス', 'メバル']
            ],
            [
                'name' => '新港 岸壁',
                'lat' => 39.2650,
                'lng' => 139.9000,
                'owner' => $others[2],
                'visibility' => 'private',
                'caution' => 'なし',
                'parking' => '公式駐車場',
                'toilet' => 'あり',
                'convenience' => 2000,
                'fish' => ['カワハギ', 'マダイ', 'マダコ']
            ],
        ]);

        // 釣行50件。自分が15件、ほかの3人で35件
        for ($i = 0; $i < 50; $i++) {
            $this->createTrip($i < 15 ? $me : $others[$i % 3], $akitaSpots, config('fishing.trip_visibility'));
        }

        // ---- 神奈川県（県の切り替えを確かめるための少しだけのデータ） ----

        $visitor = User::factory()->create([
            'name' => '湾奥アングラー',
            'email' => 'wanoku@example.com',
            'home_prefecture' => '神奈川県',
        ]);

        $kanagawaSpots = $this->createSpots('神奈川県', [
            [
                'name' => '湾奥 海釣り桟橋',
                'lat' => 35.4200,
                'lng' => 139.6800,
                'owner' => $visitor,
                'visibility' => 'public',
                'caution' => 'なし',
                'parking' => '公式駐車場',
                'toilet' => 'あり',
                'convenience' => 500,
                'fish' => ['アジ', 'サバ', 'シーバス']
            ],
            [
                'name' => '南岬 地磯',
                'lat' => 35.1400,
                'lng' => 139.6300,
                'owner' => $visitor,
                'visibility' => 'public',
                'caution' => '立入注意',
                'parking' => '路肩等',
                'toilet' => 'なし',
                'convenience' => 1200,
                'fish' => ['メジナ', 'クロダイ', 'カサゴ']
            ],
        ]);

        // 釣行6件。ほかの人の画面やフィードに出るように、全体公開か釣り場だけ隠すにする
        for ($i = 0; $i < 6; $i++) {
            $this->createTrip($visitor, $kanagawaSpots, ['public', 'spot_hidden']);
        }
    }

    /**
     * 釣り場をまとめて作る。よく釣れる魚も覚えておく
     */
    private function createSpots(string $prefecture, array $spotData): Collection
    {
        $spots = collect();
        foreach ($spotData as $data) {
            $spot = Spot::factory()->create([
                'name' => $data['name'],
                'prefecture' => $prefecture,
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'created_by' => $data['owner']->id,
                'visibility' => $data['visibility'],
                'caution_type' => $data['caution'],
                'parking_type' => $data['parking'],
                'toilet_available' => $data['toilet'],
                'convenience_distance_m' => $data['convenience'],
            ]);
            $spots->push($spot);
            $this->fishBySpot[$spot->id] = $data['fish'];
        }

        return $spots;
    }

    /**
     * 釣行を1件作る。3割くらいは坊主、残りはその釣り場でよく釣れる魚を1〜3匹
     */
    private function createTrip(User $user, Collection $spots, array $visibilities): void
    {
        // 選べる釣り場は、公開か、その人が登録した釣り場だけ（アプリと同じ決まり。NF-01）
        $choices = $spots
            ->filter(fn(Spot $spot) => $spot->visibility === 'public' || $spot->created_by === $user->id)
            ->values()
            ->all();
        $spot = fake()->randomElement($choices);

        // 時間帯を先に決めて、それに合う時刻にする
        $timeOfDay = fake()->randomElement(config('fishing.times_of_day'));
        [$from, $to] = self::HOURS[$timeOfDay];
        $date = fake()->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d');
        $wentAt = Carbon::parse($date, 'Asia/Tokyo')
            ->setTime(fake()->numberBetween($from, $to), fake()->randomElement([0, 15, 30, 45]));

        // 雪は12〜3月だけ。晴れと曇りを多めにする
        $weathers = in_array($wentAt->month, [12, 1, 2, 3], true)
            ? ['晴れ', '曇り', '曇り', '雪', '雨']
            : ['晴れ', '晴れ', '曇り', '曇り', '雨'];

        // 潮は TripFactory が日時から計算する
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'spot_id' => $spot->id,
            'went_at' => $wentAt->format('Y-m-d H:i:s'),
            'time_of_day' => $timeOfDay,
            'visibility' => fake()->randomElement($visibilities),
            'weather' => fake()->randomElement($weathers),
        ]);

        if (fake()->boolean(30)) {
            return; // 坊主
        }
        for ($n = fake()->numberBetween(1, 3); $n > 0; $n--) {
            $fish = fake()->randomElement($this->fishBySpot[$spot->id]);
            FishCatch::factory()->for($trip)->create([
                'fish_species' => $fish,
                'method' => fake()->randomElement(config('fishing.methods')),
                'length_cm' => in_array($fish, self::SMALL_FISH, true)
                    ? fake()->randomFloat(1, 12, 30)
                    : fake()->randomFloat(1, 30, 75),
            ]);
        }
    }
}
