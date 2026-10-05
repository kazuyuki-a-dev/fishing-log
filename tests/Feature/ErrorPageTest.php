<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * エラーページ（#120）。番号を出さず、ふだんの言葉で書く
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_page_is_in_plain_japanese(): void
    {
        $this->actingAs(User::factory()->create())->get('/trips/999999')
            ->assertNotFound()
            ->assertSeeText('ページが見つかりません')
            ->assertSeeText('最初の画面へ戻る')
            ->assertSeeText('前の画面に戻る')
            ->assertDontSeeText('Not Found');
    }
}
