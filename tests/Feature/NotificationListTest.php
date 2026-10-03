<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\NewSpotNotification;
use App\Notifications\NewTripNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 県内新着のお知らせを「見せる」ほうのテスト（FN-18・PG25）
 * 表示するたびに、その時点の公開範囲で文章を作ることを確かめる
 */
class NotificationListTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->author = User::factory()->create(['home_prefecture' => '秋田県']);
    }

    // 秋田県の釣り場での、ほかの人の釣行
    private function trip(string $visibility, string $spotVisibility = 'public'): Trip
    {
        $spot = Spot::factory()->create([
            'name' => 'ひみつの岩場',
            'prefecture' => '秋田県',
            'visibility' => $spotVisibility,
            'created_by' => $this->author->id,
        ]);

        return Trip::factory()->create([
            'user_id' => $this->author->id,
            'spot_id' => $spot->id,
            'visibility' => $visibility,
        ]);
    }

    public function test_public_trip_shows_the_spot_name(): void
    {
        $trip = $this->trip('public');
        $this->me->notify(new NewTripNotification([$trip->id]));

        $this->actingAs($this->me)->get('/notifications')
            ->assertOk()
            ->assertSee('秋田県のひみつの岩場で釣果が公開されました')
            ->assertSee(route('trips.show', $trip));
    }

    public function test_spot_hidden_trip_does_not_show_the_spot_name(): void
    {
        $this->me->notify(new NewTripNotification([$this->trip('spot_hidden')->id]));

        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('秋田県で釣果が公開されました')
            ->assertDontSee('ひみつの岩場');
    }

    public function test_public_trip_on_private_spot_does_not_show_the_spot_name(): void
    {
        $this->me->notify(new NewTripNotification([$this->trip('public', 'private')->id]));

        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('秋田県で釣果が公開されました')
            ->assertDontSee('ひみつの岩場');
    }

    public function test_trip_made_private_after_sending_is_hidden_from_list_and_bell(): void
    {
        $trip = $this->trip('public');
        $this->me->notify(new NewTripNotification([$trip->id]));
        $trip->update(['visibility' => 'private']);

        // ベルの数字も出ない（ダッシュボードのナビで確かめる）
        $this->actingAs($this->me)->get('/dashboard')
            ->assertDontSee('未読 1 件');
        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('まだお知らせはありません')
            ->assertDontSee('ひみつの岩場');
    }

    public function test_spot_hidden_trip_after_spot_made_private_hides_the_name(): void
    {
        $trip = $this->trip('public');
        $this->me->notify(new NewTripNotification([$trip->id]));
        // 送ったときは全体公開だったが、あとで釣り場が非公開になった
        $trip->spot->update(['visibility' => 'private']);

        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('秋田県で釣果が公開されました')
            ->assertDontSee('ひみつの岩場');
    }

    public function test_deleted_trip_is_hidden(): void
    {
        $trip = $this->trip('public');
        $this->me->notify(new NewTripNotification([$trip->id]));
        $trip->delete();

        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('まだお知らせはありません');
    }

    public function test_several_trips_show_the_count_without_spot_names(): void
    {
        $ids = [$this->trip('public')->id, $this->trip('public')->id, $this->trip('spot_hidden')->id];
        $this->me->notify(new NewTripNotification($ids));

        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('秋田県で釣果が3件公開されました')
            ->assertSee(route('feed', ['prefecture' => '秋田県']), false)
            ->assertDontSee('ひみつの岩場');
    }

    public function test_count_is_made_again_when_some_trips_become_private(): void
    {
        $first = $this->trip('spot_hidden');
        $second = $this->trip('public');
        $this->me->notify(new NewTripNotification([$first->id, $second->id]));
        $second->update(['visibility' => 'private']);

        // 1件だけ残ったときは、1件のときと同じ文章（釣り場だけ隠すなので名前なし）
        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('秋田県で釣果が公開されました')
            ->assertDontSee('2件')
            ->assertSee(route('trips.show', $first));
    }

    public function test_public_spot_is_shown_and_private_spot_is_hidden(): void
    {
        $spot = Spot::factory()->create(['name' => '新しい漁港', 'prefecture' => '秋田県', 'visibility' => 'public']);
        $this->me->notify(new NewSpotNotification($spot->id));

        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('秋田県に新しい釣り場「新しい漁港」が登録されました')
            ->assertSee(route('spots.show', $spot));

        $spot->update(['visibility' => 'private']);

        $this->actingAs($this->me)->get('/notifications')
            ->assertDontSee('新しい漁港');
    }

    public function test_bell_shows_unread_count_and_opening_the_list_marks_all_as_read(): void
    {
        $this->me->notify(new NewTripNotification([$this->trip('public')->id]));
        $this->me->notify(new NewTripNotification([$this->trip('public')->id]));

        $this->actingAs($this->me)->get('/dashboard')
            ->assertSee('未読 2 件');

        // 初めて開いたときは NEW が付き、全部既読になる
        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('NEW');
        $this->assertSame(0, $this->me->unreadNotifications()->count());

        // 2回目からは NEW が付かず、ベルの数字も出ない
        $this->actingAs($this->me)->get('/notifications')
            ->assertDontSee('NEW');
        $this->actingAs($this->me)->get('/dashboard')
            ->assertDontSee('未読');
    }

    public function test_list_shows_a_note_when_notifications_are_off(): void
    {
        $this->me->update(['notify_enabled' => false]);

        $this->actingAs($this->me)->get('/notifications')
            ->assertSee('お知らせは今 OFF です');
    }

    public function test_notifications_can_be_turned_off_and_on_in_profile(): void
    {
        $input = ['name' => $this->me->name, 'email' => $this->me->email, 'home_prefecture' => '秋田県'];

        $this->actingAs($this->me)->patch('/profile', $input + ['notify_enabled' => '0']);
        $this->assertFalse($this->me->refresh()->notify_enabled);

        $this->actingAs($this->me)->patch('/profile', $input + ['notify_enabled' => '1']);
        $this->assertTrue($this->me->refresh()->notify_enabled);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }
}
