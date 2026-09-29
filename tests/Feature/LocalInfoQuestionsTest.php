<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalInfoQuestionsTest extends TestCase
{
    use RefreshDatabase;

    /** 現地の情報がすべて空欄の釣り場（必要な項目だけ上書きできる） */
    private function spotFor(User $user, array $localInfo = []): Spot
    {
        return Spot::factory()->create($localInfo + [
            'created_by' => $user->id,
            'caution_type' => null,
            'parking_type' => null,
            'toilet_available' => null,
            'convenience_distance_m' => null,
        ]);
    }

    private function recordTrip(User $user, Spot $spot)
    {
        return $this->actingAs($user)->followingRedirects()->post('/trips', [
            'spot_id' => $spot->id,
            'went_at' => '2026-09-20T06:00',
            'time_of_day' => '朝マズメ',
            'visibility' => 'private',
        ]);
    }

    public function test_first_two_empty_items_are_asked_right_after_recording(): void
    {
        $me = User::factory()->create();

        $this->recordTrip($me, $this->spotFor($me))
            ->assertSee('の現地の情報を教えてください')
            ->assertSee('name="caution_type"', false)
            ->assertSee('name="parking_type"', false)
            ->assertDontSee('name="toilet_available"', false);
    }

    public function test_items_already_filled_are_skipped(): void
    {
        $me = User::factory()->create();

        // 注意区分は入っている → 駐車場とトイレを聞く
        $this->recordTrip($me, $this->spotFor($me, ['caution_type' => 'なし']))
            ->assertDontSee('name="caution_type"', false)
            ->assertSee('name="parking_type"', false)
            ->assertSee('name="toilet_available"', false);
    }

    public function test_nothing_is_asked_when_everything_is_filled(): void
    {
        $me = User::factory()->create();
        $spot = $this->spotFor($me, [
            'caution_type' => 'なし',
            'parking_type' => '公式駐車場',
            'toilet_available' => 'あり',
            'convenience_distance_m' => 500,
        ]);

        $this->recordTrip($me, $spot)->assertDontSee('の現地の情報を教えてください');
    }

    public function test_nothing_is_asked_when_opening_the_trip_later(): void
    {
        $me = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $this->spotFor($me)->id]);

        $this->actingAs($me)->get("/trips/{$trip->id}")
            ->assertDontSee('の現地の情報を教えてください');
    }

    public function test_answers_are_saved_without_erasing_other_items(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $spot = $this->spotFor($other, ['visibility' => 'public', 'toilet_available' => 'あり']);

        // 注意区分だけ答え、トイレは空欄のまま送る
        $this->actingAs($me)->patch("/spots/{$spot->id}/local-info", [
            'caution_type' => '注意あり',
            'toilet_available' => '',
        ])->assertSessionHas('status', '現地の情報を追加しました。ありがとうございます！');

        $spot->refresh();
        $this->assertSame('注意あり', $spot->caution_type);
        $this->assertSame('あり', $spot->toilet_available);   // 空欄で消されていない
        $this->assertSame($me->id, $spot->updated_by);         // 最終更新者は答えた人
    }

    public function test_basic_info_cannot_be_changed_through_it(): void
    {
        $me = User::factory()->create();
        $spot = $this->spotFor(User::factory()->create(), ['name' => 'もとの名前', 'visibility' => 'public']);

        $this->actingAs($me)->patch("/spots/{$spot->id}/local-info", [
            'name' => 'のっとった名前',
            'visibility' => 'private',
            'parking_type' => '路肩等',
        ]);

        $spot->refresh();
        $this->assertSame('もとの名前', $spot->name);
        $this->assertSame('public', $spot->visibility);
        $this->assertSame('路肩等', $spot->parking_type);
    }

    public function test_others_private_spot_returns_404(): void
    {
        $spot = $this->spotFor(User::factory()->create(), ['visibility' => 'private']);

        $this->actingAs(User::factory()->create())
            ->patch("/spots/{$spot->id}/local-info", ['parking_type' => '路肩等'])
            ->assertNotFound();

        $this->assertNull($spot->fresh()->parking_type);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $spot = $this->spotFor(User::factory()->create(), ['visibility' => 'public']);

        $this->patch("/spots/{$spot->id}/local-info", ['parking_type' => '路肩等'])
            ->assertRedirect('/login');
    }
}
