<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 不適切な投稿の報告（PG21・NF-04）
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create();
        $this->owner = User::factory()->create();
    }

    private function trip(string $visibility, string $spotVisibility = 'public', ?User $user = null): Trip
    {
        $spot = Spot::factory()->create(['visibility' => $spotVisibility, 'created_by' => $this->owner->id]);

        return Trip::factory()->create([
            'user_id' => ($user ?? $this->owner)->id,
            'spot_id' => $spot->id,
            'visibility' => $visibility,
        ]);
    }

    private function send(array $target, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->me)
            ->from('/somewhere')
            ->post('/reports', [...$target, 'reason' => '不適切な内容', 'detail' => '写真に人の顔が写っています']);
    }

    public function test_can_report_public_and_spot_hidden_trips(): void
    {
        foreach (['public', 'spot_hidden'] as $visibility) {
            $trip = $this->trip($visibility);

            $this->send(['trip_id' => $trip->id])
                ->assertRedirect('/somewhere')
                ->assertSessionHas('status', '報告を送りました。ご協力ありがとうございます。');

            $report = Report::where('trip_id', $trip->id)->first();
            $this->assertSame($this->me->id, $report->reporter_id);
            $this->assertNull($report->spot_id);
            $this->assertSame('不適切な内容', $report->reason);
            $this->assertSame('写真に人の顔が写っています', $report->detail);
            $this->assertSame('open', $report->fresh()->status);
        }
    }

    public function test_can_report_public_spot(): void
    {
        $spot = Spot::factory()->create(['visibility' => 'public', 'created_by' => $this->owner->id]);

        $this->send(['spot_id' => $spot->id])->assertRedirect('/somewhere');

        $this->assertTrue(Report::where('spot_id', $spot->id)->whereNull('trip_id')->exists());
    }

    public function test_cannot_report_what_i_cannot_see(): void
    {
        // 非公開の釣行と、非公開の釣り場は見えないので「ない」ことにする
        $privateTrip = $this->trip('private');
        $privateSpot = Spot::factory()->create(['visibility' => 'private', 'created_by' => $this->owner->id]);

        $this->send(['trip_id' => $privateTrip->id])->assertNotFound();
        $this->send(['spot_id' => $privateSpot->id])->assertNotFound();
        // ない番号
        $this->send(['trip_id' => 999999])->assertNotFound();

        $this->assertSame(0, Report::count());
    }

    public function test_trip_on_private_spot_can_be_reported_as_spot_hidden(): void
    {
        // 釣り場が非公開なら、全体公開の釣行も「釣り場だけ隠す」として見えている → 報告できる
        $trip = $this->trip('public', 'private');

        $this->send(['trip_id' => $trip->id])->assertRedirect('/somewhere');

        $this->assertSame(1, Report::count());
    }

    public function test_cannot_report_my_own_posts(): void
    {
        $myTrip = $this->trip('public', 'public', $this->me);
        $mySpot = Spot::factory()->create(['visibility' => 'public', 'created_by' => $this->me->id]);

        $this->send(['trip_id' => $myTrip->id])->assertForbidden();
        $this->send(['spot_id' => $mySpot->id])->assertForbidden();

        $this->assertSame(0, Report::count());
    }

    public function test_guest_cannot_report(): void
    {
        $trip = $this->trip('public');

        $this->post('/reports', ['trip_id' => $trip->id, 'reason' => '不適切な内容'])
            ->assertRedirect('/login');

        $this->assertSame(0, Report::count());
    }

    public function test_second_report_is_refused_while_open(): void
    {
        $trip = $this->trip('public');

        $this->send(['trip_id' => $trip->id]);
        $this->send(['trip_id' => $trip->id])
            ->assertRedirect('/somewhere')
            ->assertSessionHas('status', 'この投稿は、すでに報告を受け付けています。確認するまでお待ちください。');

        $this->assertSame(1, Report::count());

        // ほかの人なら報告できる
        $this->send(['trip_id' => $trip->id], User::factory()->create());
        $this->assertSame(2, Report::count());

        // 対応が終わったあとなら、同じ人がもう一度報告できる
        Report::query()->update(['status' => 'closed']);
        $this->send(['trip_id' => $trip->id]);
        $this->assertSame(3, Report::count());
    }

    public function test_input_is_checked(): void
    {
        $trip = $this->trip('public');
        $spot = Spot::factory()->create(['visibility' => 'public', 'created_by' => $this->owner->id]);

        // 理由が一覧にない・対象がない・対象が2つ
        $this->actingAs($this->me)->post('/reports', ['trip_id' => $trip->id, 'reason' => '気に入らない'])
            ->assertSessionHasErrors('reason', null, 'report');
        $this->actingAs($this->me)->post('/reports', ['reason' => '不適切な内容'])
            ->assertSessionHasErrors(['trip_id', 'spot_id'], null, 'report');
        $this->actingAs($this->me)->post('/reports', ['trip_id' => $trip->id, 'spot_id' => $spot->id, 'reason' => '不適切な内容'])
            ->assertSessionHasErrors('trip_id', null, 'report');

        $this->assertSame(0, Report::count());
    }

    public function test_status_and_reporter_cannot_be_sent_from_the_form(): void
    {
        $trip = $this->trip('public');
        $someone = User::factory()->create();

        $this->send(['trip_id' => $trip->id, 'status' => 'closed', 'reporter_id' => $someone->id]);

        $report = Report::first();
        $this->assertSame('open', $report->fresh()->status);
        $this->assertSame($this->me->id, $report->reporter_id);
    }

    public function test_report_button_is_shown_only_when_i_can_report(): void
    {
        $othersTrip = $this->trip('spot_hidden');
        $myTrip = $this->trip('public', 'public', $this->me);
        $othersSpot = Spot::factory()->create(['visibility' => 'public', 'created_by' => $this->owner->id]);

        $this->actingAs($this->me)->get(route('trips.show', $othersTrip))->assertSeeText('この釣行を報告する');
        $this->actingAs($this->me)->get(route('trips.show', $myTrip))->assertDontSeeText('この釣行を報告する');
        $this->actingAs($this->me)->get(route('spots.show', $othersSpot))->assertSeeText('この釣り場を報告する');
        $this->actingAs($this->me)->get(route('spots.show', $myTrip->spot))->assertSeeText('この釣り場を報告する');

        // ゲストには出さない
        auth()->logout();
        $this->get(route('spots.show', $othersSpot))->assertOk()->assertDontSeeText('この釣り場を報告する');
    }

    public function test_reports_are_deleted_with_the_reporter(): void
    {
        $trip = $this->trip('public');
        $this->send(['trip_id' => $trip->id]);

        $this->actingAs($this->me)->delete('/profile', ['password' => 'password']);

        $this->assertSame(0, Report::count());
    }
}
