<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理者の区分（NF-04）
 */
class MakeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_not_admin(): void
    {
        $user = User::factory()->create();

        $this->assertSame('user', $user->fresh()->role);
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_command_makes_user_admin(): void
    {
        $user = User::factory()->create(['email' => 'boss@example.com']);

        $this->artisan('app:make-admin', ['email' => 'boss@example.com'])->assertSuccessful();

        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_command_fails_for_unknown_email(): void
    {
        $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }

    public function test_role_cannot_be_changed_from_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'home_prefecture' => $user->home_prefecture,
            'role' => 'admin',
        ]);

        $this->assertFalse($user->fresh()->isAdmin());
    }
}
