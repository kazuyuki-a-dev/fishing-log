<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 作った CSS（npm run build の結果）に、大事な設定が入っているか（#225）
 * Tailwind は Blade に出てこないクラスの設定を捨てるので、JavaScript が付けるクラスの設定が消えていないかを見る
 */
class BuiltCssTest extends TestCase
{
    private function builtCss(): string
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath, '先に npm run build で画面の部品を作る');
        $manifest = json_decode(file_get_contents($manifestPath), true);

        return file_get_contents(public_path('build/' . $manifest['resources/css/app.css']['file']));
    }

    public function test_map_stays_under_the_sticky_nav(): void
    {
        // 地図（Leaflet）の重なりを地図の箱の中に閉じ込める。ないと、スクロールしたときに地図がナビより手前に出る
        $this->assertMatchesRegularExpression('/\.leaflet-container\{[^}]*isolation:isolate/', $this->builtCss());
    }
}
