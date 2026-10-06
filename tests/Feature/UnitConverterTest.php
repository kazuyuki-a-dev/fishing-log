<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 単位変換ツール（#122）
 * 計算そのものはブラウザ（JavaScript）なので、画面で確かめる。ここではページと設定の数字を確かめる
 */
class UnitConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_converter(): void
    {
        $this->get('/tools/converter')
            ->assertOk()
            ->assertSeeText('単位変換')
            ->assertSeeText('重さ（ルアー・おもり）')
            ->assertSeeText('長さ（魚・竿・ライン）')
            ->assertSeeText('ライン（糸の太さと強さ）')
            ->assertSeeText('ナイロン・フロロ')
            ->assertSeeText('エステル')
            ->assertSeeText('大体の値');
    }

    public function test_logged_in_user_can_open_the_converter(): void
    {
        $this->actingAs(User::factory()->create())->get('/tools/converter')
            ->assertOk()
            ->assertSeeText('おもりの号')
            ->assertSeeText('尺');
    }

    public function test_converter_tab_is_shown_and_active_on_its_page(): void
    {
        // ほかのページでも付箋が出る（ゲストにも）
        $this->get('/feed')->assertSee(route('tools.converter'), false);

        // このページでは「単位変換」の付箋が手前（aria-current）になる
        $this->get('/tools/converter')
            ->assertSeeInOrder(['title="単位変換"', 'aria-current="page"'], false);
    }

    public function test_nylon_table_goes_up_in_order(): void
    {
        $table = config('units.line.types.nylon.table');

        // 号も lb も、前の行より大きい（表の書き間違いを防ぐ。lb から号を逆に出すのにも必要）
        for ($i = 1; $i < count($table); $i++) {
            $this->assertGreaterThan($table[$i - 1][0], $table[$i][0]);
            $this->assertGreaterThan($table[$i - 1][1], $table[$i][1]);
        }
    }

    public function test_coefficient_defaults_are_inside_their_range(): void
    {
        foreach (['pe', 'ester'] as $type) {
            $coef = config("units.line.types.{$type}.coef");
            $this->assertGreaterThanOrEqual($coef['min'], $coef['default']);
            $this->assertLessThanOrEqual($coef['max'], $coef['default']);
        }
    }
}
