<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\SpotLocalInfoChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 現地の情報の変更の履歴と、管理者が前の値に戻すこと（#200）
 */
class LocalInfoHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $editor;
    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => '登録した人', 'email' => 'owner@example.com']);
        $this->editor = User::factory()->create(['name' => '直した人', 'email' => 'editor@example.com']);
        $this->spot = Spot::factory()->create([
            'name' => '岬の堤防',
            'prefecture' => '秋田県',
            'visibility' => 'public',
            'caution_type' => 'なし',
            'parking_type' => null,
            'parking_note' => null,
            'toilet_available' => '不明',
            'toilet_note' => null,
            'convenience_distance_m' => 500,
            'facility_note' => null,
            'notes' => '本人だけのメモ',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);
    }

    /** ほかの人が、現地の情報だけを直す（編集の画面から） */
    private function editAs(User $user, array $fields): void
    {
        $this->actingAs($user)->put("/spots/{$this->spot->id}", $fields)
            ->assertRedirect(route('spots.show', $this->spot));
    }

    public function test_only_changed_items_are_recorded_one_row_each(): void
    {
        // 駐車場とトイレを変え、コンビニまでの距離は同じ値で送る
        $this->editAs($this->editor, [
            'parking_type' => '公式駐車場',
            'toilet_available' => 'あり',
            'convenience_distance_m' => 500,
            'caution_type' => 'なし',
        ]);

        $changes = SpotLocalInfoChange::orderBy('field')->get();
        $this->assertSame(['parking_type', 'toilet_available'], $changes->pluck('field')->all());
        $this->assertSame([null, '不明'], $changes->pluck('old_value')->all());
        $this->assertSame(['公式駐車場', 'あり'], $changes->pluck('new_value')->all());
        $this->assertTrue($changes->every(fn($c) => $c->user_id === $this->editor->id && $c->action === 'edit'));
    }

    public function test_basic_info_and_private_memo_are_not_recorded(): void
    {
        // 本人が、釣り場名・県・公開設定・位置・本人だけのメモを変える
        $this->editAs($this->owner, [
            'name' => '新しい名前',
            'prefecture' => '青森県',
            'visibility' => 'public',
            'latitude' => 40.1,
            'longitude' => 140.1,
            'notes' => '書きかえたメモ',
            'caution_type' => 'なし',
            'toilet_available' => '不明',
            'convenience_distance_m' => 500,
        ]);

        $this->assertSame('新しい名前', $this->spot->fresh()->name);
        $this->assertSame(0, SpotLocalInfoChange::count());
    }

    public function test_answers_right_after_recording_are_recorded(): void
    {
        $this->actingAs($this->editor)->patch("/spots/{$this->spot->id}/local-info", [
            'parking_type' => '路肩等',
        ]);

        $change = SpotLocalInfoChange::sole();
        $this->assertSame(['parking_type', null, '路肩等', 'edit'], [$change->field, $change->old_value, $change->new_value, $change->action]);
    }

    public function test_registering_a_spot_records_the_items_that_were_filled(): void
    {
        $this->actingAs($this->owner)->post('/spots', [
            'name' => '新しい漁港',
            'prefecture' => '秋田県',
            'visibility' => 'public',
            'parking_type' => '公式駐車場',
        ]);

        $spot = Spot::where('name', '新しい漁港')->firstOrFail();
        $change = $spot->localInfoChanges()->sole();
        $this->assertSame(['parking_type', null, '公式駐車場', 'create'], [$change->field, $change->old_value, $change->new_value, $change->action]);
    }

    public function test_member_sees_the_history_on_the_karte_but_guest_does_not(): void
    {
        $this->editAs($this->editor, ['parking_type' => '公式駐車場']);

        $this->actingAs(User::factory()->create())->get("/spots/{$this->spot->id}")
            ->assertOk()
            ->assertSee('変更の履歴')
            ->assertSee('直した人')
            ->assertSeeInOrder(['駐車場', '未入力', '→', '公式駐車場'])
            ->assertSee(route('spots.history', $this->spot))
            ->assertDontSee('editor@example.com');

        auth()->logout();
        $this->get("/spots/{$this->spot->id}")
            ->assertOk()
            ->assertDontSee('変更の履歴')
            ->assertDontSee(route('spots.history', $this->spot));
    }

    public function test_history_page_shows_all_changes_to_members_only(): void
    {
        $this->editAs($this->editor, ['parking_type' => '公式駐車場']);
        $this->editAs($this->editor, ['parking_type' => '駐車不可']);

        $this->actingAs(User::factory()->create())->get("/spots/{$this->spot->id}/history")
            ->assertOk()
            // 新しい順
            ->assertSeeInOrder(['公式駐車場 → 駐車不可', '未入力 → 公式駐車場'])
            ->assertDontSee('この変更の前に戻す');

        auth()->logout();
        $this->get("/spots/{$this->spot->id}/history")->assertRedirect('/login');
    }

    public function test_history_of_others_private_spot_is_not_found(): void
    {
        $this->spot->update(['visibility' => 'private']);

        $this->actingAs($this->editor)->get("/spots/{$this->spot->id}/history")->assertNotFound();
        $this->actingAs($this->owner)->get("/spots/{$this->spot->id}/history")->assertOk();
    }

    public function test_admin_can_revert_a_change_and_the_revert_is_recorded(): void
    {
        $this->editAs($this->editor, ['parking_type' => '駐車不可']);
        $change = SpotLocalInfoChange::sole();
        $admin = User::factory()->admin()->create(['name' => '管理者']);

        $this->actingAs($admin)->get("/spots/{$this->spot->id}/history")->assertSee('この変更の前に戻す');

        $this->actingAs($admin)->post("/admin/spot-changes/{$change->id}/revert")
            ->assertRedirect(route('spots.history', $this->spot))
            ->assertSessionHas('status', '駐車場を、変更の前の値（未入力）に戻しました。');

        $this->spot->refresh();
        $this->assertNull($this->spot->parking_type);
        $this->assertSame($admin->id, $this->spot->updated_by);

        $revert = SpotLocalInfoChange::where('action', 'revert')->sole();
        $this->assertSame(['parking_type', '駐車不可', null, $admin->id, $change->id], [$revert->field, $revert->old_value, $revert->new_value, $revert->user_id, $revert->reverted_change_id]);
    }

    public function test_only_admin_can_revert(): void
    {
        $this->editAs($this->editor, ['parking_type' => '駐車不可']);
        $change = SpotLocalInfoChange::sole();

        // 登録した本人でも、管理者でなければ戻せない（ほかの人には 404）
        $this->actingAs($this->owner)->post("/admin/spot-changes/{$change->id}/revert")->assertNotFound();
        $this->assertSame('駐車不可', $this->spot->fresh()->parking_type);
    }

    public function test_change_by_a_deleted_user_is_shown_as_deleted_user(): void
    {
        $this->editAs($this->editor, ['parking_type' => '公式駐車場']);
        $this->editor->delete();

        $this->assertNull(SpotLocalInfoChange::sole()->user_id);
        $this->actingAs($this->owner)->get("/spots/{$this->spot->id}/history")
            ->assertSee('退会したユーザー')
            ->assertDontSee('直した人');
    }
}
