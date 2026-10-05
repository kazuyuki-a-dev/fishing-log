<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\KeepRecording;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 継続カウンタと気づきカード（FN-10）
 * 「今日」は 2026-10-05 にしておく
 */
class KeepRecordingTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-05 12:00:00'));
        $this->me = User::factory()->create();
        $this->spot = Spot::factory()->create(['visibility' => 'public']);
    }

    // 自分の釣行を作る。$caught が true なら1匹釣れたことにする
    private function trip(string $wentAt, bool $caught = false, array $conditions = [], ?User $user = null): Trip
    {
        $trip = Trip::factory()->create([
            'user_id' => ($user ?? $this->me)->id,
            'spot_id' => $this->spot->id,
            'went_at' => $wentAt,
            'visibility' => 'private',
            'tide' => null,
            'time_of_day' => null,
            'weather' => null,
            ...$conditions,
        ]);
        if ($caught) {
            FishCatch::factory()->create(['trip_id' => $trip->id]);
        }

        return $trip;
    }

    private function build(): array
    {
        return app(KeepRecording::class)->build($this->me, today());
    }

    public function test_counter_counts_this_month_streak_and_last_year(): void
    {
        // 去年の11月から今月まで毎月（年をまたぐ）。今月は2回（坊主も1回に数える）
        foreach (['2025-11-10', '2025-12-10', '2026-01-10', '2026-02-10', '2026-03-10', '2026-04-10',
            '2026-05-10', '2026-06-10', '2026-07-10', '2026-08-10', '2026-09-10', '2026-10-01'] as $date) {
            $this->trip($date, true);
        }
        $this->trip('2026-10-03');

        // 去年の10月は1回。9月がないので、連続には入らない
        $this->trip('2025-10-20');
        $this->trip('2025-08-20');

        // ほかの人の釣行は数えない
        $this->trip('2026-10-02', true, [], User::factory()->create());

        $counter = $this->build()['counter'];

        $this->assertSame(10, $counter['month']);
        $this->assertSame(2, $counter['thisMonth']);
        // 2025-10 から 2026-10 まで続いている（13か月）。2025-09 で途切れる
        $this->assertSame(13, $counter['streak']);
        $this->assertTrue($counter['recordedThisMonth']);
        $this->assertSame(1, $counter['lastYear']);
    }

    public function test_streak_continues_until_this_month_ends(): void
    {
        // 8月・9月は記録あり、今月はまだ → 先月までの続きで「2か月」
        $this->trip('2026-08-15');
        $this->trip('2026-09-15');

        $counter = $this->build()['counter'];

        $this->assertSame(0, $counter['thisMonth']);
        $this->assertSame(2, $counter['streak']);
        $this->assertFalse($counter['recordedThisMonth']);

        $this->actingAs($this->me)->get('/dashboard')
            ->assertOk()
            ->assertSeeText('今月記録すると 3か月に')
            ->assertSeeText('去年の10月は記録なし');
    }

    public function test_streak_is_zero_when_last_month_has_no_record(): void
    {
        // 8月はあるが9月がない → 途切れている
        $this->trip('2026-08-15');

        $counter = $this->build()['counter'];

        $this->assertSame(0, $counter['streak']);
    }

    public function test_insights_need_three_trips(): void
    {
        $this->trip('2026-09-01', true, ['tide' => '大潮']);
        $this->trip('2026-09-02', true, ['tide' => '大潮']);

        $insights = $this->build()['insights'];

        $this->assertSame([], $insights['cards']);
        $this->assertSame(1, $insights['remaining']);

        $this->actingAs($this->me)->get('/dashboard')
            ->assertOk()
            ->assertSeeText('釣行をあと 1件記録すると');
    }

    public function test_insights_pick_the_best_rate_for_each_condition(): void
    {
        // 潮：大潮 3回中2回（67%）、中潮 2回中2回（100%）→ 中潮
        $this->trip('2026-09-01', true, ['tide' => '大潮', 'time_of_day' => '朝マズメ', 'weather' => '晴れ']);
        $this->trip('2026-09-02', true, ['tide' => '大潮', 'time_of_day' => '朝マズメ', 'weather' => '晴れ']);
        $this->trip('2026-09-03', false, ['tide' => '大潮', 'time_of_day' => '夜', 'weather' => '晴れ']);
        $this->trip('2026-09-04', true, ['tide' => '中潮', 'time_of_day' => '夜', 'weather' => '曇り']);
        $this->trip('2026-09-05', true, ['tide' => '中潮', 'time_of_day' => '夜', 'weather' => '曇り']);
        // 小潮は1回だけで当たり（100%）。2回行っていないので選ばない
        $this->trip('2026-09-06', true, ['tide' => '小潮', 'time_of_day' => '日中', 'weather' => '雨']);
        // 空欄の釣行は、その条件の計算に入れない（件数には入る）
        $this->trip('2026-09-07', false);

        // ほかの人の記録は数えない（入れると中潮が 100% ではなくなる）
        $other = User::factory()->create();
        $this->trip('2026-09-08', false, ['tide' => '中潮', 'time_of_day' => '夜', 'weather' => '曇り'], $other);
        $this->trip('2026-09-09', false, ['tide' => '中潮', 'time_of_day' => '夜', 'weather' => '曇り'], $other);

        $insights = $this->build()['insights'];

        $this->assertSame(7, $insights['tripCount']);
        $cards = collect($insights['cards'])->keyBy('field');

        $this->assertSame(['value' => '中潮', 'visits' => 2, 'caught' => 2], collect($cards['tide'])->only('value', 'visits', 'caught')->all());
        // 時間帯：朝マズメ 2回中2回（100%）、夜 3回中2回 → 朝マズメ
        $this->assertSame('朝マズメ', $cards['time_of_day']['value']);
        // 天候：晴れ 3回中2回、曇り 2回中2回 → 曇り
        $this->assertSame('曇り', $cards['weather']['value']);

        $this->actingAs($this->me)->get('/dashboard')
            ->assertOk()
            ->assertSeeText('中潮のときに釣れているようです')
            ->assertSeeText('中潮で 2回行って 2回釣れた')
            ->assertSeeText('自分の釣行 7件から');
    }

    public function test_insights_prefer_more_visits_when_rates_are_the_same(): void
    {
        // 大潮 2回中1回、中潮 4回中2回（どちらも 50%）→ 行った回数が多い中潮
        $this->trip('2026-09-01', true, ['tide' => '大潮']);
        $this->trip('2026-09-02', false, ['tide' => '大潮']);
        $this->trip('2026-09-03', true, ['tide' => '中潮']);
        $this->trip('2026-09-04', true, ['tide' => '中潮']);
        $this->trip('2026-09-05', false, ['tide' => '中潮']);
        $this->trip('2026-09-06', false, ['tide' => '中潮']);

        $cards = collect($this->build()['insights']['cards']);

        $this->assertCount(1, $cards);
        $this->assertSame('中潮', $cards->first()['value']);
    }

    public function test_no_card_when_nothing_was_caught(): void
    {
        // 3回とも坊主 → 一度も釣れていない条件はカードにしない
        $this->trip('2026-09-01', false, ['tide' => '大潮']);
        $this->trip('2026-09-02', false, ['tide' => '大潮']);
        $this->trip('2026-09-03', false, ['tide' => '大潮']);

        $insights = $this->build()['insights'];

        $this->assertSame([], $insights['cards']);
        $this->assertSame(0, $insights['remaining']);

        $this->actingAs($this->me)->get('/dashboard')
            ->assertOk()
            ->assertSeeText('まだはっきりした傾向は見えていません');
    }

    public function test_dashboard_shows_counter_with_no_trips(): void
    {
        // 1件目の前でも画面が空にならない（REQ-01）
        $this->actingAs($this->me)->get('/dashboard')
            ->assertOk()
            ->assertSeeText('今月の釣行')
            ->assertSeeText('今月記録すると 1か月に')
            ->assertSeeText('釣行をあと 3件記録すると');
    }
}
