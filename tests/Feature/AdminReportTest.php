<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Report;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 報告を確かめて対応する管理画面（NF-04）
 */
class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->create(['name' => '投稿した人']);
        $this->reporter = User::factory()->create(['name' => '報告した人']);
    }

    private function trip(array $attributes = [], array $spotAttributes = []): Trip
    {
        $spot = Spot::factory()->create(['visibility' => 'public', 'created_by' => $this->owner->id, ...$spotAttributes]);

        return Trip::factory()->create(['user_id' => $this->owner->id, 'spot_id' => $spot->id, 'visibility' => 'public', ...$attributes]);
    }

    // 報告を1件作る。$target は Trip か Spot
    private function report(Trip|Spot $target, string $status = 'open', ?User $reporter = null): Report
    {
        $report = new Report(['reason' => '不適切な内容', 'detail' => '詳しい内容です']);
        $report->reporter_id = ($reporter ?? $this->reporter)->id;
        $report->{$target instanceof Trip ? 'trip_id' : 'spot_id'} = $target->id;
        $report->status = $status;
        $report->save();

        return $report;
    }

    public function test_only_admin_can_use_admin_pages(): void
    {
        $report = $this->report($this->trip());

        // 一般の人には 404（管理画面があることを知らせない）
        $this->actingAs($this->reporter)->get('/admin/reports')->assertNotFound();
        $this->actingAs($this->reporter)->patch("/admin/reports/{$report->id}", ['status' => 'closed'])->assertNotFound();
        $this->actingAs($this->reporter)->post("/admin/reports/{$report->id}/hide")->assertNotFound();

        // ゲストはログインへ
        auth()->logout();
        $this->get('/admin/reports')->assertRedirect('/login');

        $this->assertSame('open', $report->fresh()->status);
        $this->assertSame('public', $report->trip->fresh()->visibility);

        $this->actingAs($this->admin)->get('/admin/reports')->assertOk();
    }

    public function test_nav_shows_open_count_only_to_admin(): void
    {
        $trip = $this->trip();
        $this->report($trip);
        $this->report($trip, 'open', User::factory()->create());
        $this->report($trip, 'closed', User::factory()->create());

        $this->actingAs($this->admin)->get('/dashboard')->assertSeeText('報告（未対応 2件）');
        $this->actingAs($this->reporter)->get('/dashboard')->assertDontSeeText('報告（未対応');
    }

    public function test_list_starts_with_open_and_can_switch(): void
    {
        $open = $this->report($this->trip(['went_at' => '2026-09-01 06:00']));
        $closed = $this->report($this->trip(['went_at' => '2026-08-02 06:00']), 'closed');

        $this->actingAs($this->admin)->get('/admin/reports')
            ->assertOk()
            ->assertSeeText("#{$open->id}")
            ->assertDontSeeText("#{$closed->id}")
            ->assertSeeText('未対応（1）')
            ->assertSeeText('対応完了（1）')
            ->assertSeeText('すべて（2）');

        $this->actingAs($this->admin)->get('/admin/reports?status=closed')
            ->assertSeeText("#{$closed->id}")
            ->assertDontSeeText("#{$open->id}");

        $this->actingAs($this->admin)->get('/admin/reports?status=all')
            ->assertSeeText("#{$open->id}")
            ->assertSeeText("#{$closed->id}");

        // 一覧にない値は「未対応」にする
        $this->actingAs($this->admin)->get('/admin/reports?status=wrong')
            ->assertSeeText("#{$open->id}")
            ->assertDontSeeText("#{$closed->id}");
    }

    public function test_list_shows_report_and_post_without_private_notes(): void
    {
        // 釣り場だけ隠すの釣行。管理者には釣り場名を出す
        $trip = $this->trip(['visibility' => 'spot_hidden', 'notes' => '本人だけの釣行メモ'], ['name' => '秘密の堤防', 'notes' => '本人だけの釣り場メモ']);
        FishCatch::factory()->create(['trip_id' => $trip->id, 'fish_species' => 'アジ', 'notes' => '本人だけの釣果メモ']);
        $this->report($trip);
        $this->report($trip, 'closed', User::factory()->create());
        $this->report($trip->spot);

        $this->actingAs($this->admin)->get('/admin/reports')
            ->assertOk()
            ->assertSeeText('釣行への報告：不適切な内容')
            ->assertSeeText('釣り場への報告：不適切な内容')
            ->assertSeeText('詳しい内容です')
            ->assertSeeText('報告した人：報告した人')
            ->assertSeeText('この投稿への報告は全部で 2件')
            ->assertSeeText('この投稿への報告は全部で 1件')
            ->assertSeeText('秘密の堤防')
            ->assertSeeText('投稿した人')
            ->assertSeeText('アジ')
            // 本人にだけ見せるメモは、管理者にも出さない
            ->assertDontSeeText('本人だけの釣行メモ')
            ->assertDontSeeText('本人だけの釣果メモ')
            ->assertDontSeeText('本人だけの釣り場メモ');
    }

    public function test_admin_can_change_status(): void
    {
        $report = $this->report($this->trip());

        $this->actingAs($this->admin)->from('/admin/reports')
            ->patch("/admin/reports/{$report->id}", ['status' => 'reviewed'])
            ->assertRedirect('/admin/reports')
            ->assertSessionHas('status', "報告 #{$report->id} を「確認済み」にしました。");

        $this->assertSame('reviewed', $report->fresh()->status);

        // 一覧にない状態は受け付けない
        $this->actingAs($this->admin)->patch("/admin/reports/{$report->id}", ['status' => 'deleted'])
            ->assertSessionHasErrors('status');
        $this->assertSame('reviewed', $report->fresh()->status);
    }

    public function test_hide_trip_makes_it_private_and_closes_its_reports(): void
    {
        $trip = $this->trip();
        $report = $this->report($trip);
        $reviewed = $this->report($trip, 'reviewed', User::factory()->create());
        // ほかの投稿への報告は変えない
        $other = $this->report($this->trip());

        $this->actingAs($this->admin)->from('/admin/reports')
            ->post("/admin/reports/{$report->id}/hide")
            ->assertRedirect('/admin/reports')
            ->assertSessionHas('status', '釣行を非公開にし、この投稿への報告を「対応完了」にしました。');

        $this->assertSame('private', $trip->fresh()->visibility);
        $this->assertSame('closed', $report->fresh()->status);
        $this->assertSame('closed', $reviewed->fresh()->status);
        $this->assertSame('open', $other->fresh()->status);

        // ほかの人の画面から消える
        $this->assertFalse(Trip::forFeed()->whereKey($trip->id)->exists());
        $this->actingAs($this->reporter)->get(route('trips.show', $trip))->assertNotFound();
        // 投稿した人は自分の画面で見られる
        $this->actingAs($this->owner)->get(route('trips.show', $trip))->assertOk();

        // 非公開になったものには、ボタンを出さない
        $this->actingAs($this->admin)->get('/admin/reports?status=all')
            ->assertSeeText('非公開になっています');
    }

    public function test_hide_spot_makes_it_private_without_changing_last_update(): void
    {
        $spot = Spot::factory()->create(['visibility' => 'public', 'created_by' => $this->owner->id]);
        $spot->timestamps = false;
        $spot->forceFill(['updated_at' => '2026-09-01 10:00:00'])->save();
        $report = $this->report($spot);

        $this->actingAs($this->admin)->post("/admin/reports/{$report->id}/hide")
            ->assertSessionHas('status', '釣り場を非公開にし、この投稿への報告を「対応完了」にしました。');

        $spot = $spot->fresh();
        $this->assertSame('private', $spot->visibility);
        // 釣り場の「最終更新」の日付は、管理者の操作で変えない
        $this->assertSame('2026-09-01 10:00:00', $spot->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('closed', $report->fresh()->status);

        $this->actingAs($this->reporter)->get(route('spots.show', $spot))->assertNotFound();
    }
}
