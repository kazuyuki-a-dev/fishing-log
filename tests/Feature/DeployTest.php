<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * インターネットに公開するための準備（#136）
 * - お試し用アカウントは、プロフィール・パスワードの変更と退会ができない
 * - 公開環境のシーダーでは、README に書いた「password」でログインできない
 * - お試しデータは、データが空のときだけ入れる
 */
class DeployTest extends TestCase
{
    use RefreshDatabase;

    private function demoUser(): User
    {
        return User::factory()->create(['email' => config('fishing.demo_email'), 'name' => 'お試しユーザー']);
    }

    public function test_demo_profile_page_shows_the_notice_instead_of_forms(): void
    {
        $this->actingAs($this->demoUser())->get('/profile')
            ->assertOk()
            ->assertSeeText('お試し用のアカウントです')
            ->assertDontSee('name="current_password"', false);
    }

    public function test_demo_account_cannot_change_profile_or_password(): void
    {
        $demo = $this->demoUser();

        $this->actingAs($demo)->patch('/profile', [
            'name' => '書きかえ',
            'email' => 'changed@example.com',
            'home_prefecture' => '東京都',
        ])->assertRedirect('/profile');

        $this->actingAs($demo)->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $demo->refresh();
        $this->assertSame('お試しユーザー', $demo->name);
        $this->assertSame(config('fishing.demo_email'), $demo->email);
        $this->assertTrue(Hash::check('password', $demo->password));
    }

    public function test_demo_account_cannot_be_deleted(): void
    {
        $demo = $this->demoUser();

        $this->actingAs($demo)->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/profile');

        $this->assertNotNull($demo->fresh());
    }

    public function test_production_seeder_does_not_use_the_readme_password(): void
    {
        // 公開環境として、お試しデータを入れる
        $this->app['env'] = 'production';
        config(['fishing.admin_password' => 'secret-admin-pass']);
        // 本番環境では確認を聞かれるので、公開環境の起動のときと同じく --force で入れる
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        // 管理者は秘密のパスワード。README の「password」ではログインできない
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secret-admin-pass', $admin->password));
        $this->assertFalse(Hash::check('password', $admin->password));

        // お試し用アカウントは、README に書く公開用のパスワード
        $demo = User::where('email', config('fishing.demo_email'))->firstOrFail();
        $this->assertTrue(Hash::check(config('fishing.demo_password'), $demo->password));
        $this->assertSame(45, $demo->trips()->count());

        // 開発用の test@ は作らない。ほかのダミーの釣り人も「password」ではログインできない
        $this->assertNull(User::where('email', 'test@example.com')->first());
        foreach (['minato@example.com', 'surf@example.com', 'iso@example.com', 'wanoku@example.com', 'angler1@example.com'] as $email) {
            $this->assertFalse(Hash::check('password', User::where('email', $email)->firstOrFail()->password), $email);
        }
    }

    public function test_seed_if_empty_seeds_only_once(): void
    {
        // 空なら、お試しデータを入れる
        $this->artisan('app:seed-if-empty')->assertSuccessful();
        $count = User::count();
        $this->assertGreaterThan(0, $count);

        // 2回目は何もしない（使う人が入れたデータを消さない）
        $this->artisan('app:seed-if-empty')
            ->expectsOutput('データがあるので、お試しデータは入れません。')
            ->assertSuccessful();
        $this->assertSame($count, User::count());
    }
}
