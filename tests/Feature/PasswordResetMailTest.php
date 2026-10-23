<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * パスワード再設定のメールの中身（#202）
 * 本当に送るのは公開環境の Brevo（開発環境は Mailpit で受け止める）。テストでは送らずに中身だけ確かめる
 */
class PasswordResetMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_mail_is_in_japanese_with_the_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'angler@example.com']);

        $this->post('/forgot-password', ['email' => 'angler@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            $this->assertSame('【FishingLog】パスワード再設定のご案内', $mail->subject);
            $this->assertSame('パスワード再設定', $mail->actionText);
            $this->assertStringContainsString('/reset-password/' . $notification->token, $mail->actionUrl);
            $this->assertStringContainsString('email=angler%40example.com', $mail->actionUrl);
            $this->assertContains('このパスワード再設定リンクの有効期限は60分です。', $mail->outroLines);

            return true;
        });
    }

    public function test_unknown_address_does_not_send_mail(): void
    {
        Notification::fake();

        // 登録していないアドレスには送らない
        $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        Notification::assertNothingSent();
    }
}
