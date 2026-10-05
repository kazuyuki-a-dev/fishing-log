<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * アプリの使い方のページ（#116）
 */
class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_guide(): void
    {
        $this->get('/guide')
            ->assertOk()
            ->assertSeeText('使い方')
            ->assertSeeText('行く前に（プランナーとカルテ）')
            ->assertSeeText('記録する')
            ->assertSeeText('安全とマナー')
            // ゲストには会員登録の案内
            ->assertSee(route('register'), false);
    }

    public function test_logged_in_user_can_open_the_guide(): void
    {
        $this->actingAs(User::factory()->create())->get('/guide')
            ->assertOk()
            ->assertSeeText('ふり返る')
            ->assertDontSeeText('会員登録してください');
    }

    public function test_guide_is_linked_from_footer_menu_and_top(): void
    {
        // フッター・≡ メニュー（どの画面にも出る）
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertSee(route('guide'), false);

        // トップページ（ゲスト）
        auth()->logout();
        $this->get('/')->assertOk()->assertSeeText('使い方を見る');
    }
}
