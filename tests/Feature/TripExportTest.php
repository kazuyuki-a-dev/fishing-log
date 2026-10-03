<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 釣行・釣果の CSV 出力（FN-04・PG17）
 */
class TripExportTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $other;
    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->other = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->spot = Spot::factory()->create([
            'name' => '北の堤防',
            'prefecture' => '秋田県',
            'visibility' => 'public',
            'latitude' => 39.7186,
            'longitude' => 140.1023,
        ]);
    }

    /**
     * 釣行を作る。$catches は [魚種, 釣り方, サイズ] の並び（空なら坊主）
     */
    private function trip(array $attributes = [], array $catches = [['アジ', 'エサ', 20]]): Trip
    {
        $trip = Trip::factory()->create(array_merge([
            'user_id' => $this->me->id,
            'spot_id' => $this->spot->id,
            'went_at' => '2026-05-03 06:00',
            'time_of_day' => '朝マズメ',
            'tide' => '大潮',
            'weather' => '晴れ',
            'visibility' => 'private',
            'notes' => null,
        ], $attributes));

        foreach ($catches as [$species, $method, $length]) {
            FishCatch::factory()->create([
                'trip_id' => $trip->id,
                'fish_species' => $species,
                'method' => $method,
                'length_cm' => $length,
                'method_detail' => null,
                'weight_g' => null,
                'notes' => null,
            ]);
        }

        return $trip;
    }

    /**
     * CSV を取ってきて、BOM を外して行ごとの配列にする
     */
    private function csv(string $query = '', ?User $user = null): array
    {
        $response = $this->actingAs($user ?? $this->me)->get("/trips/export{$query}")->assertOk();
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\u{FEFF}", $content, 'BOM が付いていない');

        $lines = array_filter(explode("\n", substr($content, strlen("\u{FEFF}"))));

        return array_map('str_getcsv', array_values($lines));
    }

    public function test_one_row_per_fish_and_one_row_for_bozu(): void
    {
        $this->trip(['went_at' => '2026-05-03 06:00', 'notes' => '風が強い'], [['アジ', 'エサ', 20], ['サバ', 'ルアー', 30]]);
        $this->trip(['went_at' => '2026-04-01 06:00'], []); // 坊主

        $rows = $this->csv();

        $this->assertSame(['釣行日時', '時間帯', '釣り場', '県', '潮', '天候', '公開範囲', '釣行メモ', '魚種', '釣り方', '釣り方詳細', 'サイズ(cm)', '重さ(g)', '釣果メモ'], $rows[0]);
        $this->assertCount(4, $rows); // 見出し＋魚2匹＋坊主1行
        $this->assertSame(['2026-05-03 06:00', '朝マズメ', '北の堤防', '秋田県', '大潮', '晴れ', '非公開', '風が強い', 'アジ', 'エサ', '', '20.0', '', ''], $rows[1]);
        $this->assertSame('サバ', $rows[2][8]);
        // 坊主は魚の欄が空
        $this->assertSame(['2026-04-01 06:00', '', '', '', '', '', ''], [$rows[3][0], ...array_slice($rows[3], 8)]);
    }

    public function test_only_my_trips_even_with_public_scope(): void
    {
        $this->trip();
        $this->trip(['user_id' => $this->other->id, 'visibility' => 'public'], [['ヒラメ', 'ルアー', 50]]);

        foreach (['', '?scope=public', '?scope=public&prefecture=all'] as $query) {
            $rows = $this->csv($query);

            $this->assertCount(2, $rows, "{$query} でほかの人の釣行が出た");
            $this->assertSame('アジ', $rows[1][8]);
        }
    }

    public function test_filters_are_applied_and_only_matching_fish_are_rows(): void
    {
        $this->trip(['tide' => '大潮'], [['アジ', 'エサ', 20], ['サバ', 'エサ', 30]]);
        $this->trip(['tide' => '小潮'], [['アジ', 'エサ', 18]]);
        $this->trip(['tide' => '大潮'], []); // 坊主：魚種で絞ると出ない

        $rows = $this->csv('?tide=大潮&species=アジ');

        $this->assertCount(2, $rows);
        $this->assertSame(['大潮', 'アジ'], [$rows[1][4], $rows[1][8]]);
    }

    public function test_location_is_not_included(): void
    {
        $this->trip();

        $content = $this->actingAs($this->me)->get('/trips/export')->streamedContent();

        $this->assertStringNotContainsString('39.7', $content);
        $this->assertStringNotContainsString('140.1', $content);
    }

    public function test_formula_like_text_is_made_safe_for_excel(): void
    {
        $this->trip(['notes' => '=HYPERLINK("http://example.com")']);

        $this->assertSame("'=HYPERLINK(\"http://example.com\")", $this->csv()[1][7]);
    }

    public function test_file_name_has_the_date(): void
    {
        $this->travelTo('2026-10-03 12:00');
        $this->trip();

        $this->actingAs($this->me)->get('/trips/export')
            ->assertDownload('fishinglog-20261003.csv');
    }

    public function test_button_is_shown_only_for_mine_with_results(): void
    {
        $this->actingAs($this->me)->get('/trips')->assertDontSee('CSV で保存');

        $this->trip();
        $this->trip(['user_id' => $this->other->id, 'visibility' => 'public']);

        $this->actingAs($this->me)->get('/trips?tide=大潮')
            ->assertSee('CSV で保存')
            ->assertSee(route('trips.export', ['tide' => '大潮']), false);
        $this->actingAs($this->me)->get('/trips?scope=public')->assertDontSee('CSV で保存');
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/trips/export')->assertRedirect('/login');
    }
}
