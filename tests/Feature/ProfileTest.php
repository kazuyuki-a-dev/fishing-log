<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'home_prefecture' => '青森県',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('青森県', $user->home_prefecture);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'home_prefecture' => $user->home_prefecture,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_photos_are_deleted_with_the_account(): void
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create();

        // 自分の釣果の写真と、ほかの人の釣果の写真
        $mine = FishCatch::factory()->create(['trip_id' => Trip::factory()->create(['user_id' => $user->id])->id, 'image_path' => 'catches/mine.jpg']);
        $theirs = FishCatch::factory()->create(['trip_id' => Trip::factory()->create(['user_id' => $other->id])->id, 'image_path' => 'catches/theirs.jpg']);
        $disk->put($mine->image_path, 'photo');
        $disk->put($theirs->image_path, 'photo');

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        // 退会した人の写真のファイルは消える（URL を知っていても見られない）。ほかの人の写真は残る（#118）
        $disk->assertMissing('catches/mine.jpg');
        $disk->assertExists('catches/theirs.jpg');
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_home_prefecture_must_be_one_of_47_prefectures_on_update(): void
    {
        $user = User::factory()->create(['home_prefecture' => '秋田県']);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'home_prefecture' => '竜宮城',
                'email' => $user->email,
            ]);

        $response->assertSessionHasErrors('home_prefecture')->assertRedirect('/profile');
        $this->assertSame('秋田県', $user->refresh()->home_prefecture);
    }
}
