<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * URL の番号のところに文字が来たら、ページがない（404）にする（#241）
 * PostgreSQL は、番号の列を文字で探すとエラー（500）になるため、ルートで数字だけを受け付ける
 */
class RouteNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_with_a_word_instead_of_a_number_are_not_found(): void
    {
        $this->get('/spots/abc')->assertNotFound();
        $this->get('/spots/1abc')->assertNotFound();
    }

    public function test_member_pages_with_a_word_instead_of_a_number_are_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/trips/abc')->assertNotFound();
        $this->actingAs($user)->get('/trips/abc/edit')->assertNotFound();
        $this->actingAs($user)->get('/spots/abc/edit')->assertNotFound();
    }

    public function test_fixed_pages_still_open(): void
    {
        $user = User::factory()->create();

        // 番号の代わりに決まった言葉が来るページは、そのまま開く
        $this->actingAs($user)->get('/trips/create')->assertOk();
        $this->actingAs($user)->get('/spots/create')->assertOk();
    }
}
