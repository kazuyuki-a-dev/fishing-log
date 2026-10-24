<?php

namespace Tests\Feature\Auth;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * 会員登録のときにメールアドレスを確かめる（#213）
 * 確かめるまでは、ログインが必要なページは確認の画面になる。ゲストも見られるページは今までどおり
 */
class EmailVerificationRequiredTest extends TestCase
{
    use RefreshDatabase;

    private function register(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', [
            'name' => '新しい釣り人',
            'home_prefecture' => '秋田県',
            'email' => 'newcomer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);
    }

    public function test_registration_sends_a_japanese_verification_mail(): void
    {
        Notification::fake();

        $this->register()->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'newcomer@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
            $mail = $notification->toMail($user);
            $this->assertSame('【FishingLog】メールアドレスの確認のお願い', $mail->subject);
            $this->assertSame('メールアドレスの確認', $mail->actionText);
            $this->assertStringContainsString('/verify-email/' . $user->id . '/', $mail->actionUrl);

            return true;
        });
    }

    public function test_unverified_member_cannot_use_member_pages(): void
    {
        $user = User::factory()->unverified()->create();

        foreach (['/dashboard', '/trips', '/trips/create', '/bests', '/analysis/heatmap', '/notifications'] as $url) {
            $this->actingAs($user)->get($url)->assertRedirect(route('verification.notice'));
        }
        // 記録を送ることもできない
        $this->actingAs($user)->post('/trips', [])->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_member_can_still_see_public_pages_and_profile(): void
    {
        $user = User::factory()->unverified()->create(['home_prefecture' => '秋田県']);
        $spot = Spot::factory()->create(['visibility' => 'public']);

        // ゲストも見られるページと、アドレスを直すためのプロフィールは開ける
        foreach (['/feed', '/planner', '/spots', route('spots.show', $spot), '/tools/converter', '/guide', '/profile'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_verify_page_shows_the_address_and_help(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'newcomer@example.com']);

        $this->actingAs($user)->get('/verify-email')
            ->assertOk()
            ->assertSee('newcomer@example.com')
            ->assertSee('メールが届かないとき')
            ->assertSee('確認のメールをもう一度送る');
    }

    public function test_registration_still_succeeds_when_mail_cannot_be_sent(): void
    {
        // メール送信サービスにつながらなくても、500 にせず、登録して確認の画面で案内する
        Notification::shouldReceive('send')->andThrow(new TransportException('Connection could not be established'));
        Log::shouldReceive('error')->once()->withArgs(fn($message) => $message === '確認のメールを送れませんでした');

        $this->register()
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-send-failed');

        $this->assertAuthenticated();
        $this->assertNotNull(User::where('email', 'newcomer@example.com')->first());
    }

    public function test_resend_shows_a_message_when_mail_cannot_be_sent(): void
    {
        $user = User::factory()->unverified()->create();
        Notification::shouldReceive('send')->andThrow(new TransportException('Connection could not be established'));
        Log::shouldReceive('error')->once();

        $this->actingAs($user)->from('/verify-email')->post('/email/verification-notification')
            ->assertRedirect('/verify-email')
            ->assertSessionHas('status', 'verification-send-failed');
    }

    public function test_seeded_people_are_already_verified(): void
    {
        // お試し用アカウントなどはファクトリーで作るので、確認済み（今までどおりログインして使える）
        $this->assertTrue(User::factory()->create()->hasVerifiedEmail());
    }
}
