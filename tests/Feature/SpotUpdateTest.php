<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $other;
    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->spot = Spot::factory()->create([
            'name' => '元の名前',
            'prefecture' => '秋田県',
            'visibility' => 'public',
            'parking_type' => null,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);
    }

    public function test_owner_can_update_basic_info_and_local_info(): void
    {
        $this->actingAs($this->owner)->put("/spots/{$this->spot->id}", [
            'name' => '新しい名前',
            'prefecture' => '青森県',
            'visibility' => 'private',
            'parking_type' => '公式駐車場',
        ])->assertRedirect(route('spots.show', $this->spot));

        $this->spot->refresh();
        $this->assertSame('新しい名前', $this->spot->name);
        $this->assertSame('青森県', $this->spot->prefecture);
        $this->assertSame('private', $this->spot->visibility);
        $this->assertSame('公式駐車場', $this->spot->parking_type);
    }

    public function test_others_can_update_only_local_info_even_if_they_send_basic_info(): void
    {
        // ほかの人が、開発者ツールで釣り場名や公開設定まで送りつけてきた
        $this->actingAs($this->other)->put("/spots/{$this->spot->id}", [
            'name' => '乗っ取り堤防',
            'prefecture' => '沖縄県',
            'visibility' => 'private',
            'parking_type' => '路肩等',
        ])->assertRedirect(route('spots.show', $this->spot));

        $this->spot->refresh();
        // 現地の情報は変わる
        $this->assertSame('路肩等', $this->spot->parking_type);
        // 釣り場名・県・公開設定は変わらない
        $this->assertSame('元の名前', $this->spot->name);
        $this->assertSame('秋田県', $this->spot->prefecture);
        $this->assertSame('public', $this->spot->visibility);
        // 最後に更新した人は、ほかの人になる（FN-14）
        $this->assertSame($this->other->id, $this->spot->updated_by);
    }

    public function test_edit_form_shows_basic_fields_only_to_the_owner(): void
    {
        $this->actingAs($this->owner)->get("/spots/{$this->spot->id}/edit")
            ->assertOk()
            ->assertSee('name="name"', false);

        $this->actingAs($this->other)->get("/spots/{$this->spot->id}/edit")
            ->assertOk()
            ->assertDontSee('name="name"', false)
            ->assertSee('登録した人だけが変更できます');
    }

    public function test_others_cannot_edit_a_private_spot(): void
    {
        $this->spot->update(['visibility' => 'private']);

        $this->actingAs($this->other)->get("/spots/{$this->spot->id}/edit")->assertNotFound();
        $this->actingAs($this->other)->put("/spots/{$this->spot->id}", ['parking_type' => '路肩等'])->assertNotFound();

        $this->assertNull($this->spot->refresh()->parking_type);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get("/spots/{$this->spot->id}/edit")->assertRedirect(route('login'));
    }
}
