<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\ConditionMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ダミーの釣行の印と、記録の内訳（#211）
 * 2024-01-19 は小潮で、時期は 12〜2月。2024-01-17・18 は小潮、2024-01-11 は大潮
 */
class DummyTripTest extends TestCase
{
    use RefreshDatabase;

    private Spot $spot;

    private User $dummy;

    private User $real;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->admin()->create();
        $this->spot = Spot::factory()->create(['name' => '秋田港', 'prefecture' => '秋田県', 'visibility' => 'public', 'created_by' => $admin->id]);
        // 釣行の一覧は最初メインフィールドで絞るので、県をそろえる（#152）
        $this->dummy = User::factory()->sample()->create(['name' => 'ダミーの釣り人', 'home_prefecture' => '秋田県']);
        $this->real = User::factory()->create(['name' => '本物の釣り人', 'home_prefecture' => '秋田県']);
    }

    // 全体公開の釣行を作る。$caught が true なら1匹釣れたことにする
    private function trip(User $user, string $wentAt, string $timeOfDay, bool $caught, string $visibility = 'public'): Trip
    {
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'spot_id' => $this->spot->id,
            'went_at' => $wentAt,
            'time_of_day' => $timeOfDay,
            'visibility' => $visibility,
        ]);
        if ($caught) {
            FishCatch::factory()->create(['trip_id' => $trip->id]);
        }

        return $trip;
    }

    public function test_only_trips_of_dummy_people_are_marked_in_the_feed(): void
    {
        $this->trip($this->dummy, '2024-01-17 06:00', '朝マズメ', true);
        $this->trip($this->real, '2024-01-18 06:00', '朝マズメ', true);

        $html = $this->get('/feed?prefecture=' . urlencode('秋田県'))->assertOk()->getContent();

        // 印は1つだけ（ダミーの人の釣行）。新しい順なので、本物の釣行のあとにダミーの印が出る
        $this->assertSame(1, substr_count($html, '>ダミー</span>'));
        $this->assertMatchesRegularExpression('/2024\/01\/18.*?本物の釣り人.*?2024\/01\/17.*?ダミー<\/span>.*?ダミーの釣り人/s', $html);
    }

    public function test_trip_detail_says_it_is_a_dummy(): void
    {
        $dummyTrip = $this->trip($this->dummy, '2024-01-17 06:00', '朝マズメ', true);
        $realTrip = $this->trip($this->real, '2024-01-18 06:00', '朝マズメ', true);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get(route('trips.show', $dummyTrip))
            ->assertOk()
            ->assertSee('この釣行はダミーです。');
        $this->actingAs($viewer)->get(route('trips.show', $realTrip))
            ->assertOk()
            ->assertDontSee('この釣行はダミーです。')
            ->assertDontSee('>ダミー</span>', false);
    }

    public function test_dummy_persons_own_trips_are_marked_in_their_list(): void
    {
        // お試し用アカウントなど、ダミーの人が自分の一覧を見ても印が付く（自分の分は、ログインしている人の印で見る）
        $this->trip($this->dummy, '2024-01-17 06:00', '朝マズメ', true, 'private');

        $this->actingAs($this->dummy)->get('/trips')
            ->assertOk()
            ->assertSee('>ダミー</span>', false);
        $this->actingAs($this->real)->get('/trips')
            ->assertOk()
            ->assertDontSee('>ダミー</span>', false);
    }

    public function test_karte_shows_dummy_count_and_breakdown(): void
    {
        $this->trip($this->dummy, '2024-01-17 06:00', '朝マズメ', true);
        $this->trip($this->dummy, '2024-01-18 20:00', '夜', false);
        $this->trip($this->real, '2024-01-11 12:00', '日中', true);

        // 時間帯は指定なし → 時期と潮で探す。小潮の2回は釣れた1回があるのでぴったり一致を使う
        $this->get(route('spots.show', ['spot' => $this->spot, 'date' => '2024-01-19']))
            ->assertOk()
            ->assertSee('2回行って 1回釣れた')
            ->assertSee('うちダミー 2回')
            ->assertSee('記録の内訳')
            ->assertSeeInOrder(['時間帯', '朝マズメ', '夜', '潮', '小潮'])
            // 時間帯で絞っていないので、記録がない時間帯を書く
            ->assertSee('日中・夕マズメ・終日の記録はまだありません。')
            // カルテの履歴にも印（ダミーの人の2件だけ）
            ->assertSee('>ダミー</span>', false);
    }

    public function test_karte_does_not_list_missing_times_when_filtered_by_time(): void
    {
        $this->trip($this->dummy, '2024-01-17 06:00', '朝マズメ', true);

        // 朝マズメで絞ったぴったり一致なので、ほかの時間帯がないのは当たり前。書かない
        $this->get(route('spots.show', ['spot' => $this->spot, 'date' => '2024-01-19', 'time_of_day' => '朝マズメ']))
            ->assertOk()
            ->assertSee('記録の内訳')
            ->assertDontSee('の記録はまだありません。');
    }

    public function test_planner_shows_dummy_count_and_breakdown(): void
    {
        $this->trip($this->dummy, '2024-01-17 06:00', '朝マズメ', true);
        $this->trip($this->real, '2024-01-18 20:00', '夜', true);

        $this->get('/planner?date=2024-01-19&prefecture=' . urlencode('秋田県'))
            ->assertOk()
            ->assertSee('2回行って 2回釣れた')
            ->assertSee('うちダミー 1回')
            ->assertSee('記録の内訳を見る')
            ->assertSee('朝マズメ 1回（釣れた 1）')
            ->assertSee('夜 1回（釣れた 1）')
            ->assertSee('小潮 2回（釣れた 2）')
            ->assertSee('2024年 2回（釣れた 2）');
    }

    public function test_no_dummy_count_when_all_records_are_real(): void
    {
        $this->trip($this->real, '2024-01-17 06:00', '朝マズメ', true);

        $this->get('/planner?date=2024-01-19&prefecture=' . urlencode('秋田県'))
            ->assertOk()
            ->assertSee('1回行って 1回釣れた')
            ->assertDontSee('うちダミー');
    }

    public function test_breakdown_counts_only_visible_trips_in_order(): void
    {
        $this->trip($this->real, '2024-01-17 20:00', '夜', false);
        $this->trip($this->real, '2024-01-18 06:00', '朝マズメ', true);
        $this->trip($this->real, '2024-01-11 06:00', '朝マズメ', true);
        // ほかの人の非公開の釣行は、ゲストには数えない
        $this->trip($this->dummy, '2024-01-17 12:00', '日中', true, 'private');

        $visible = Trip::where('trips.spot_id', $this->spot->id)->visibleWithSpotTo(null);
        $match = app(ConditionMatcher::class)->matchQuery($visible, Carbon::parse('2024-01-19'), '小潮', null);
        $breakdown = app(ConditionMatcher::class)->breakdown($match['matched']);

        // ぴったり一致（時期と小潮）の2回。時間帯は決まった言葉の順（朝マズメ → 夜）
        $this->assertSame(0, $match['samples']);
        $this->assertSame(['朝マズメ' => ['visits' => 1, 'caught' => 1], '夜' => ['visits' => 1, 'caught' => 0]], $breakdown['time_of_day']);
        $this->assertSame(['小潮' => ['visits' => 2, 'caught' => 1]], $breakdown['tide']);
        $this->assertSame(['2024' => ['visits' => 2, 'caught' => 1]], $breakdown['year']);
    }
}
