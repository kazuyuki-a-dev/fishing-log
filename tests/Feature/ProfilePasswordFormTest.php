<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * プロフィールのパスワードの変更は、ふだんは閉じておき、押すと開く（#215）
 */
class ProfilePasswordFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_form_is_closed_at_first(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertSee('パスワードを変える')
            ->getContent();

        $this->assertFalse($this->isOpen($html));
    }

    public function test_password_form_stays_open_when_there_is_an_error(): void
    {
        $user = User::factory()->create();

        // 今のパスワードをまちがえると、開いたままエラーを見せる
        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/profile');

        $this->assertTrue($this->isOpen($this->actingAs($user)->get('/profile')->getContent()));
    }

    public function test_password_form_stays_open_after_saving(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/profile');

        $this->assertTrue($this->isOpen($this->actingAs($user)->get('/profile')->getContent()));
    }

    // パスワードの変更の details が開いているか
    private function isOpen(string $html): bool
    {
        $this->assertSame(1, preg_match('/<details([^>]*)>\s*<summary[^>]*>\s*<span class="crayon-subheading">パスワードを変える/', $html, $m));

        return (bool) preg_match('/\bopen\b/', $m[1]);
    }
}
