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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * 開発用のデータ（NF-07）
 * - 釣り場は全国の実在する港と湖（database/seeders/data/real_spots.php。名前と位置の出どころはそのファイルの先頭）
 * - 釣行と釣果は開発用のダミー。プランナー（FN-16）とカルテ（FN-09）を確かめられるだけの記録を、過去3年分入れる
 * - ダミーの釣行は、国が釣りができると案内している「釣り文化振興モデル港」に付ける
 *   （ふつうの港は立入禁止・釣り禁止の場所が多く、そこに釣果があるように見せないため）
 *   例外として、秋田県だけはプランナーで比べられるように、船川港と能代港にも付ける（#160。港は全部「注意あり」）
 * - 魚ごとに釣れやすい月と時間帯を決めて、季節のあるデータにする（#160）
 * - 公開環境（APP_ENV=production）では、だれでもログインできないようにパスワードを変える（#136）
 *   管理者は秘密のパスワード、メインの釣り人はお試し用アカウント（README に書く）、ほかのダミーの釣り人はランダム
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

    // ダミーの釣行で釣れる魚（港の防波堤でよく釣れるもの。config/fishing.php の魚種から）と、季節（#160）
    // 1〜12月の釣れやすさ（0＝ほとんど釣れない、1＝ときどき、3＝よく釣れる）と、釣れやすい時間帯
    private const HARBOR_FISH = [
        'アジ' => ['months' => [0, 0, 0, 0, 1, 3, 3, 3, 3, 3, 3, 1], 'times' => ['朝マズメ', '夕マズメ', '夜']],
        'サバ' => ['months' => [0, 0, 0, 0, 0, 1, 3, 3, 3, 3, 1, 0], 'times' => ['朝マズメ', '日中']],
        'イワシ' => ['months' => [0, 0, 0, 0, 1, 3, 3, 3, 3, 1, 0, 0], 'times' => ['朝マズメ', '日中']],
        'カサゴ' => ['months' => [3, 3, 3, 1, 1, 1, 1, 1, 1, 1, 3, 3], 'times' => ['夕マズメ', '夜']],
        'メバル' => ['months' => [3, 3, 3, 3, 3, 1, 0, 0, 0, 0, 1, 3], 'times' => ['夕マズメ', '夜']],
        'クロダイ' => ['months' => [0, 0, 1, 3, 3, 3, 3, 3, 3, 3, 1, 0], 'times' => ['朝マズメ', '日中']],
        'シーバス' => ['months' => [0, 0, 1, 3, 3, 1, 1, 1, 3, 3, 3, 1], 'times' => ['朝マズメ', '夕マズメ', '夜']],
        'アイナメ' => ['months' => [3, 3, 1, 0, 0, 0, 0, 0, 0, 1, 3, 3], 'times' => ['朝マズメ', '日中']],
    ];

    // 時間帯の選ばれやすさ（重み）
    private const TIME_WEIGHTS = ['朝マズメ' => 30, '日中' => 20, '夕マズメ' => 25, '夜' => 20, '終日' => 5];

    // 釣行の数（#160）。秋田県の3港と、ほかのモデル港1か所あたり
    private const AKITA_TRIPS = ['秋田港' => 120, '船川港' => 60, '能代港' => 60];
    private const OTHER_MODEL_PORT_TRIPS = 30;

    // 自分（test@・お試し用アカウント）の釣行の数。秋田県の3港に付ける
    private const MY_TRIPS = 45;

    // 港の注意のメモ（NF-04：釣り禁止の場所を、釣りができるように見せないため）
    private const PORT_NOTE = '港の中は立入禁止・釣り禁止の場所が多いです。釣りができる場所は、現地の表示や港の管理者の案内を確かめてください。';

    private const LAKE_NOTE = '釣りの決まり（遊漁券・期間・釣り方・リリースの決まりなど）は、漁協や県の案内を確かめてください。';

    public function run(): void
    {
        // 毎回同じダミーデータになるように、でたらめの元（シード）を固定する
        fake()->seed(2026);

        // 公開環境かどうか。公開環境では、README に書いた「password」でログインできないようにする（#136）
        $public = app()->environment('production');
        // ほかの人がログインできないパスワード（公開環境のダミーの釣り人用）
        $locked = fn() => ['password' => Str::random(40)];

        // ---- 管理者（NF-04）。実在の釣り場の登録者にする。釣行は持たせない ----
        $admin = User::factory()->admin()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'home_prefecture' => '東京都',
            'notify_enabled' => false,
            ...($public ? ['password' => config('fishing.admin_password') ?: Str::random(40)] : []),
        ]);

        // ---- 全国の実在する釣り場 ----
        $spots = $this->createRealSpots($admin);

        // ---- 秋田県（メインのダミーデータ） ----

        // 自分でログインして確かめるためのユーザー（パスワードは password）
        // 公開環境では、お試し用の専用アカウント（メール・パスワードは config/fishing.php。README に書く）
        $me = User::factory()->create($public
            ? [
                'name' => 'お試しユーザー',
                'email' => config('fishing.demo_email'),
                'password' => config('fishing.demo_password'),
                'home_prefecture' => '秋田県',
            ]
            : [
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'home_prefecture' => '秋田県',
            ]);

        // ほかの釣り人3人（メインフィールドは同じ県）
        $others = collect([
            ['name' => '港の釣り人', 'email' => 'minato@example.com'],
            ['name' => 'サーフ好き', 'email' => 'surf@example.com'],
            ['name' => '磯の常連', 'email' => 'iso@example.com'],
        ])->map(fn(array $user) => User::factory()->create([...$user, 'home_prefecture' => '秋田県', ...($public ? $locked() : [])]));

        // ---- 釣行（#160）。秋田県の3港：自分が45件、ほかの3人で残り ----
        $akitaPorts = $spots->filter(fn(array $row) => array_key_exists($row['data']['name'], self::AKITA_TRIPS) && $row['data']['prefecture'] === '秋田県');
        $akitaTrips = $akitaPorts->flatMap(fn(array $row) => array_fill(0, self::AKITA_TRIPS[$row['data']['name']], $row['spot']))->shuffle(2026)->values();
        foreach ($akitaTrips as $i => $spot) {
            $this->createTrip($i < self::MY_TRIPS ? $me : $others[$i % 3], $spot);
        }

        // ---- ほかの県のモデル港。県ごとに地元の釣り人を1人作り、その人の釣行にする ----
        $otherModelPorts = $spots->filter(fn(array $row) => $row['data']['model'] && $row['data']['prefecture'] !== '秋田県');
        $locals = [];
        foreach ($otherModelPorts as $row) {
            $prefecture = $row['data']['prefecture'];
            $locals[$prefecture] ??= User::factory()->create([
                'name' => "{$prefecture}の釣り人",
                'email' => 'angler' . (count($locals) + 1) . '@example.com',
                'home_prefecture' => $prefecture,
                ...($public ? $locked() : []),
            ]);
            for ($n = 0; $n < self::OTHER_MODEL_PORT_TRIPS; $n++) {
                $this->createTrip($locals[$prefecture], $row['spot']);
            }
        }

        // ---- 神奈川県（県の切り替えを確かめるための人。神奈川にはモデル港がないので釣行はない） ----
        User::factory()->create([
            'name' => '湾奥アングラー',
            'email' => 'wanoku@example.com',
            'home_prefecture' => '神奈川県',
            ...($public ? $locked() : []),
        ]);

        // ---- 全体公開の釣果のいくつかに、サンプルの魚のイラストを付ける（#148） ----
        Artisan::call('app:attach-sample-photos');
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
     * ダミーの釣行を1件作る（#160）
     * 時間帯を決め、その月と時間帯に釣れやすい魚を選ぶ。季節外れの月や冬は坊主になりやすい
     */
    private function createTrip(User $user, Spot $spot): void
    {
        // 時間帯を先に決めて、それに合う時刻にする
        $timeOfDay = $this->weighted(self::TIME_WEIGHTS);
        [$from, $to] = self::HOURS[$timeOfDay];
        $date = fake()->dateTimeBetween('-3 years', '-1 day')->format('Y-m-d');
        $wentAt = Carbon::parse($date, 'Asia/Tokyo')
            ->setTime(fake()->numberBetween($from, $to), fake()->randomElement([0, 15, 30, 45]));
        $month = $wentAt->month;

        // 雪は12〜3月だけ。晴れと曇りを多めにする
        $weathers = in_array($month, [12, 1, 2, 3], true)
            ? ['晴れ', '曇り', '曇り', '雪', '雨']
            : ['晴れ', '晴れ', '曇り', '曇り', '雨'];

        // 潮は TripFactory が日時から計算する
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'spot_id' => $spot->id,
            'went_at' => $wentAt->format('Y-m-d H:i:s'),
            'time_of_day' => $timeOfDay,
            'visibility' => fake()->randomElement(config('fishing.trip_visibility')),
            'weather' => fake()->randomElement($weathers),
        ]);

        // その月と時間帯の釣れやすさ。釣れやすい時間帯なら2倍
        $weights = [];
        foreach (self::HARBOR_FISH as $fish => $season) {
            $weight = $season['months'][$month - 1] * (in_array($timeOfDay, $season['times'], true) ? 2 : 1);
            if ($weight > 0) {
                $weights[$fish] = $weight;
            }
        }

        // 坊主になる割合：冬（12〜2月）は5割、春と晩秋は3割、夏〜秋は2割
        $bozu = match (true) {
            in_array($month, [12, 1, 2], true) => 50,
            in_array($month, [3, 4, 11], true) => 30,
            default => 20,
        };
        if ($weights === [] || fake()->boolean($bozu)) {
            return; // 坊主
        }

        for ($n = fake()->numberBetween(1, 3); $n > 0; $n--) {
            $fish = $this->weighted($weights);
            FishCatch::factory()->for($trip)->create([
                'fish_species' => $fish,
                'method' => fake()->randomElement(config('fishing.methods')),
                'length_cm' => in_array($fish, self::SMALL_FISH, true)
                    ? fake()->randomFloat(1, 12, 30)
                    : fake()->randomFloat(1, 30, 75),
            ]);
        }
    }

    /**
     * 重みつきで1つ選ぶ（例：['朝マズメ' => 30, '日中' => 20] なら、朝マズメが選ばれやすい）
     *
     * @param  array<string, int>  $weights
     */
    private function weighted(array $weights): string
    {
        $pick = fake()->numberBetween(1, array_sum($weights));
        foreach ($weights as $name => $weight) {
            $pick -= $weight;
            if ($pick <= 0) {
                return $name;
            }
        }

        return array_key_first($weights);
    }
}
