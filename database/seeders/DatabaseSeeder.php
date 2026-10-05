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
 * 開発用のデータ（NF-07）
 * - 釣り場は全国の実在する港と湖（database/seeders/data/real_spots.php。名前と位置の出どころはそのファイルの先頭）
 * - 釣行と釣果は開発用のダミー。プランナー（FN-16）とカルテ（FN-09）を確かめられるだけの記録を、秋田県に集めて入れる
 * - ダミーの釣行は、国が釣りができると案内している「釣り文化振興モデル港」にだけ付ける
 *   （ふつうの港は立入禁止・釣り禁止の場所が多く、そこに釣果があるように見せないため）
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

    // ダミーの釣行で釣れる魚（港の防波堤でよく釣れるもの。config/fishing.php の魚種から）
    private const HARBOR_FISH = ['アジ', 'サバ', 'イワシ', 'カサゴ', 'メバル', 'クロダイ', 'シーバス', 'アイナメ'];

    // 港の注意のメモ（NF-04：釣り禁止の場所を、釣りができるように見せないため）
    private const PORT_NOTE = '港の中は立入禁止・釣り禁止の場所が多いです。釣りができる場所は、現地の表示や港の管理者の案内を確かめてください。';

    private const LAKE_NOTE = '釣りの決まり（遊漁券・期間・釣り方・リリースの決まりなど）は、漁協や県の案内を確かめてください。';

    public function run(): void
    {
        // 毎回同じダミーデータになるように、でたらめの元（シード）を固定する
        fake()->seed(2026);

        // ---- 管理者（NF-04）。実在の釣り場の登録者にする。釣行は持たせない ----
        $admin = User::factory()->admin()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'home_prefecture' => '東京都',
            'notify_enabled' => false,
        ]);

        // ---- 全国の実在する釣り場 ----
        $spots = $this->createRealSpots($admin);

        // ---- 秋田県（メインのダミーデータ） ----

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

        // 釣行50件。自分が15件、ほかの3人で35件。秋田県のモデル港に付ける
        $akitaModelPorts = $spots->filter(fn(array $row) => $row['data']['prefecture'] === '秋田県' && $row['data']['model'])
            ->pluck('spot');
        for ($i = 0; $i < 50; $i++) {
            $this->createTrip($i < 15 ? $me : $others[$i % 3], $akitaModelPorts, config('fishing.trip_visibility'));
        }

        // ---- 神奈川県（県の切り替えを確かめるための人。神奈川にはモデル港がないので釣行はない） ----
        User::factory()->create([
            'name' => '湾奥アングラー',
            'email' => 'wanoku@example.com',
            'home_prefecture' => '神奈川県',
        ]);
    }

    /**
     * 実在の釣り場を作る。公開・登録者は管理者
     * 駐車場やトイレなどの現地の情報は分からないので空にしておく（使う人が入れる。FN-14）
     *
     * @return Collection<int, array{spot: Spot, data: array}>
     */
    private function createRealSpots(User $admin): Collection
    {
        return collect(require __DIR__ . '/data/real_spots.php')->map(function (array $data) use ($admin) {
            $note = $data['kind'] === '湖' ? self::LAKE_NOTE : self::PORT_NOTE;
            if ($data['model']) {
                $place = $data['model_place'] ? "（釣りができる場所：{$data['model_place']}）" : '';
                $note .= "\n国の「釣り文化振興モデル港」です{$place}。開いている日や時間は、港の管理者の案内を確かめてください。";
            }

            $spot = Spot::forceCreate([
                'name' => $data['name'],
                'prefecture' => $data['prefecture'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'visibility' => 'public',
                'caution_type' => '注意あり',
                'facility_note' => $note,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            return ['spot' => $spot, 'data' => $data];
        });
    }

    /**
     * ダミーの釣行を1件作る。3割くらいは坊主、残りは港でよく釣れる魚を1〜3匹
     */
    private function createTrip(User $user, Collection $spots, array $visibilities): void
    {
        $spot = fake()->randomElement($spots->values()->all());

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
            $fish = fake()->randomElement(self::HARBOR_FISH);
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
