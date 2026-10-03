<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\NewSpotNotification;
use App\Notifications\NewTripNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 県内新着のお知らせを「送る」ほうのテスト（FN-18）
 */
class NotificationSendTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $neighbor;
    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();
        // 天気の API には本当に取りに行かない
        Http::fake();

        // 投稿する人と、同じ秋田県の人（お知らせ ON）
        $this->author = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->neighbor = User::factory()->create(['home_prefecture' => '秋田県']);
        // 秋田県の公開の釣り場（位置なし＝天気は取りに行かない）
        $this->spot = Spot::factory()->create([
            'prefecture' => '秋田県',
            'visibility' => 'public',
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    // 釣行の登録・更新のときに送る入力
    private function tripInput(array $overrides = []): array
    {
        return array_merge([
            'spot_id' => $this->spot->id,
            'went_at' => '2026-05-03 06:00',
            'time_of_day' => '朝マズメ',
            'visibility' => 'public',
        ], $overrides);
    }

    // 釣り場の登録のときに送る入力
    private function spotInput(array $overrides = []): array
    {
        return array_merge([
            'name' => '新しい漁港',
            'prefecture' => '秋田県',
            'visibility' => 'public',
        ], $overrides);
    }

    public function test_public_trip_is_sent_only_to_members_of_the_same_prefecture_with_notify_on(): void
    {
        $aomori = User::factory()->create(['home_prefecture' => '青森県']);
        $off = User::factory()->create(['home_prefecture' => '秋田県', 'notify_enabled' => false]);

        $this->actingAs($this->author)->post('/trips', $this->tripInput());

        $this->assertSame(1, $this->neighbor->notifications()->count());
        $this->assertSame(NewTripNotification::class, $this->neighbor->notifications()->first()->type);
        // 自分・ほかの県・OFF の人には届かない
        $this->assertSame(0, $this->author->notifications()->count());
        $this->assertSame(0, $aomori->notifications()->count());
        $this->assertSame(0, $off->notifications()->count());
    }

    public function test_spot_hidden_trip_is_also_sent(): void
    {
        $this->actingAs($this->author)->post('/trips', $this->tripInput(['visibility' => 'spot_hidden']));

        $this->assertSame(1, $this->neighbor->notifications()->count());
    }

    public function test_private_trip_is_not_sent(): void
    {
        $this->actingAs($this->author)->post('/trips', $this->tripInput(['visibility' => 'private']));

        $this->assertSame(0, $this->neighbor->notifications()->count());
    }

    public function test_only_the_trip_number_is_saved_not_the_text(): void
    {
        $this->actingAs($this->author)->post('/trips', $this->tripInput());

        $trip = $this->author->trips()->first();
        $this->assertSame(['trip_ids' => [$trip->id]], $this->neighbor->notifications()->first()->data);
    }

    public function test_changing_private_trip_to_public_sends_only_once(): void
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->author->id,
            'spot_id' => $this->spot->id,
            'visibility' => 'private',
        ]);

        // 非公開 → 公開 → 非公開 → 公開 と切り替えても、お知らせは1件だけ
        foreach (['public', 'private', 'public'] as $visibility) {
            $this->actingAs($this->author)->put("/trips/{$trip->id}", $this->tripInput(['visibility' => $visibility]));
        }

        $this->assertSame(1, $this->neighbor->notifications()->count());
        $this->assertNotNull($trip->refresh()->notified_at);
    }

    public function test_editing_a_trip_that_was_already_public_does_not_send(): void
    {
        // シーダーで入れた公開の釣行のように、notified_at が空でも、もとから公開なら送らない
        $trip = Trip::factory()->create([
            'user_id' => $this->author->id,
            'spot_id' => $this->spot->id,
            'visibility' => 'public',
        ]);

        $this->actingAs($this->author)->put("/trips/{$trip->id}", $this->tripInput(['notes' => '直した']));

        $this->assertSame(0, $this->neighbor->notifications()->count());
    }

    public function test_bulk_registration_sends_one_notification_per_prefecture(): void
    {
        $aomoriSpot = Spot::factory()->create([
            'prefecture' => '青森県',
            'visibility' => 'public',
            'latitude' => null,
            'longitude' => null,
        ]);
        $aomori = User::factory()->create(['home_prefecture' => '青森県']);

        $row = fn(Spot $spot, string $wentAt) => [
            'went_at' => $wentAt,
            'spot_id' => $spot->id,
            'time_of_day' => '朝マズメ',
            'fish_species' => config('fishing.fish_species')[0],
            'method' => config('fishing.methods')[0],
        ];

        // 秋田で3つの釣行、青森で1つの釣行
        $this->actingAs($this->author)->post(route('trips.bulk-store'), [
            'visibility' => 'public',
            'rows' => [
                $row($this->spot, '2026-05-01T06:00'),
                $row($this->spot, '2026-05-02T06:00'),
                $row($this->spot, '2026-05-03T06:00'),
                $row($aomoriSpot, '2026-05-04T06:00'),
            ],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(1, $this->neighbor->notifications()->count());
        $this->assertCount(3, $this->neighbor->notifications()->first()->data['trip_ids']);
        $this->assertSame(1, $aomori->notifications()->count());
        $this->assertCount(1, $aomori->notifications()->first()->data['trip_ids']);
    }

    public function test_bulk_registration_as_private_is_not_sent(): void
    {
        $this->actingAs($this->author)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [[
                'went_at' => '2026-05-01T06:00',
                'spot_id' => $this->spot->id,
                'time_of_day' => '朝マズメ',
            ]],
        ])->assertRedirect(route('trips.index'));

        $this->assertSame(0, $this->neighbor->notifications()->count());
    }

    public function test_public_spot_is_sent(): void
    {
        $this->actingAs($this->author)->post('/spots', $this->spotInput());

        $spot = Spot::where('name', '新しい漁港')->first();
        $this->assertSame(1, $this->neighbor->notifications()->count());
        $this->assertSame(NewSpotNotification::class, $this->neighbor->notifications()->first()->type);
        $this->assertSame(['spot_id' => $spot->id], $this->neighbor->notifications()->first()->data);
        $this->assertSame(0, $this->author->notifications()->count());
    }

    public function test_private_spot_is_not_sent(): void
    {
        $this->actingAs($this->author)->post('/spots', $this->spotInput(['visibility' => 'private']));

        $this->assertSame(0, $this->neighbor->notifications()->count());
    }

    public function test_changing_private_spot_to_public_sends_only_once(): void
    {
        $spot = Spot::factory()->create([
            'created_by' => $this->author->id,
            'prefecture' => '秋田県',
            'visibility' => 'private',
        ]);

        foreach (['public', 'private', 'public'] as $visibility) {
            $this->actingAs($this->author)->put("/spots/{$spot->id}", $this->spotInput(['visibility' => $visibility]));
        }

        $this->assertSame(1, $this->neighbor->notifications()->count());
    }

    public function test_deleting_account_also_deletes_own_notifications(): void
    {
        $this->actingAs($this->author)->post('/trips', $this->tripInput());
        $this->assertSame(1, $this->neighbor->notifications()->count());

        $this->actingAs($this->neighbor)->delete('/profile', ['password' => 'password']);

        $this->assertDatabaseCount('notifications', 0);
    }
}
