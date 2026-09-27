<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_spots_and_own_spots_are_listed_but_others_private_spots_are_not(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);
        $other = User::factory()->create(['home_prefecture' => '秋田県']);

        Spot::factory()->create(['name' => 'みんなの公開の釣り場', 'prefecture' => '秋田県', 'visibility' => 'public', 'created_by' => $other->id]);
        Spot::factory()->create(['name' => '自分の非公開の釣り場', 'prefecture' => '秋田県', 'visibility' => 'private', 'created_by' => $me->id]);
        Spot::factory()->create(['name' => '他人の秘密の釣り場', 'prefecture' => '秋田県', 'visibility' => 'private', 'created_by' => $other->id]);

        $response = $this->actingAs($me)->get('/spots');

        $response->assertOk();
        $response->assertSee('みんなの公開の釣り場');
        $response->assertSee('自分の非公開の釣り場');
        $response->assertDontSee('他人の秘密の釣り場');
    }

    public function test_spots_are_filtered_by_home_prefecture_by_default(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);

        Spot::factory()->create(['name' => '秋田の釣り場', 'prefecture' => '秋田県', 'visibility' => 'public']);
        Spot::factory()->create(['name' => '青森の釣り場', 'prefecture' => '青森県', 'visibility' => 'public']);

        $this->actingAs($me)->get('/spots')
            ->assertSee('秋田の釣り場')
            ->assertDontSee('青森の釣り場');

        $this->actingAs($me)->get('/spots?prefecture=all')
            ->assertSee('秋田の釣り場')
            ->assertSee('青森の釣り場');
    }
}
