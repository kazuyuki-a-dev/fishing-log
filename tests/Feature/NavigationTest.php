<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ナビの付箋とスマホの ≡ メニュー（#124）
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /** スマホの ≡ メニューの部分だけを取り出す（付箋やベルにも同じリンクがあるので） */
    private function mobileMenu(string $html): string
    {
        $start = strpos($html, '<!-- Responsive Navigation Menu -->');
        $end = strpos($html, '<!-- Responsive Settings Options -->');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    /** $routes のリンクが、この順でメニューに出ているか */
    private function assertLinksInOrder(string $menu, array $routes): void
    {
        $last = -1;
        foreach ($routes as $name) {
            $position = strpos($menu, 'href="' . route($name) . '"');
            $this->assertNotFalse($position, "{$name} がメニューにない");
            $this->assertGreaterThan($last, $position, "{$name} の位置がちがう");
            $last = $position;
        }
    }

    public function test_menu_lists_tab_pages_first_then_notifications_and_guide(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->getContent();

        // 付箋と同じ順 → お知らせ → 使い方（お知らせが付箋のページの間に入らない）
        $this->assertLinksInOrder($this->mobileMenu($html), [
            'dashboard', 'planner', 'spots.index', 'trips.index', 'feed', 'tools.converter',
            'notifications.index', 'guide',
        ]);
    }

    public function test_guest_menu_shows_only_pages_for_guests(): void
    {
        $menu = $this->mobileMenu($this->get('/feed')->assertOk()->getContent());

        $this->assertLinksInOrder($menu, ['spots.index', 'feed', 'tools.converter', 'guide']);
        $this->assertStringNotContainsString('href="' . route('dashboard') . '"', $menu);
        $this->assertStringNotContainsString('href="' . route('notifications.index') . '"', $menu);
    }

    public function test_admin_menu_shows_reports_before_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $menu = $this->mobileMenu($this->actingAs($admin)->get('/dashboard')->assertOk()->getContent());

        $this->assertLinksInOrder($menu, ['tools.converter', 'admin.reports.index', 'notifications.index']);
        $this->assertStringContainsString('報告（未対応 0件）', $menu);
    }

    public function test_admin_tab_is_short_on_narrow_screens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // せまい画面は「報告 0」、1280px 以上は「報告（未対応 0件）」。title には長いほう
        $this->actingAs($admin)->get('/dashboard')
            ->assertSee('<span class="xl:hidden">報告 0</span>', false)
            ->assertSee('title="報告（未対応 0件）"', false);
    }
}
