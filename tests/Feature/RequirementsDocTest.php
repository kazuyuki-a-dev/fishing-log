<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 要件ごとの対応表（docs/requirements.md）が、定義書と実際のテストに合っているか（#198）
 * 表を直し忘れたり、テストの名前を変えたりしたら、ここで気づけるようにする
 */
class RequirementsDocTest extends TestCase
{
    private function doc(): string
    {
        return file_get_contents(base_path('docs/requirements.md'));
    }

    /** 表の行の「| 番号 | 要件 | 状態 |」から、番号 => 状態 を取り出す */
    private function statuses(string $prefix): array
    {
        preg_match_all('/^\| (' . $prefix . '-?\d{2}) \| [^|]+ \| ([^|]+?) \|/mu', $this->doc(), $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn($m) => [$m[1] => trim($m[2])])->all();
    }

    public function test_every_requirement_in_the_spec_is_in_the_table(): void
    {
        $spec = file_get_contents(base_path('docs/spec.md'));

        foreach (['FN-' => 'FN', 'NF-' => 'NF', 'PG' => 'PG'] as $pattern => $prefix) {
            preg_match_all('/^\| (' . $pattern . '\d{2}) \|/m', $spec, $matches);
            $inSpec = array_values(array_unique($matches[1]));
            $this->assertNotEmpty($inSpec, "定義書に {$prefix} の番号が見つからない");
            $this->assertSame($inSpec, array_keys($this->statuses($prefix)), "{$prefix} の番号が定義書と対応表で合わない");
        }
    }

    public function test_only_the_four_statuses_are_used(): void
    {
        $allowed = ['完了', '一部対応', '未対応', 'お試し環境の制約'];
        foreach (['FN', 'NF', 'PG'] as $prefix) {
            foreach ($this->statuses($prefix) as $number => $status) {
                $this->assertContains($status, $allowed, "{$number} の状態「{$status}」");
            }
        }
    }

    public function test_every_row_has_a_method_result_and_date(): void
    {
        // 欄：番号 | 要件 | 状態 | 受け入れの条件 | 確認方法 | テスト・手順 | 結果 | 確認日 | 備考
        preg_match_all('/^\| ((?:FN-|NF-|PG)\d{2}) \|(.*)\|$/mu', $this->doc(), $rows, PREG_SET_ORDER);
        $this->assertCount(50, $rows, 'FN 18・NF 7・PG 25 の行がない');

        foreach ($rows as [, $number, $rest]) {
            $cells = array_map('trim', explode('|', $rest));
            $this->assertCount(8, $cells, "{$number} の欄の数");
            [, , , $method, $procedure, $result, $date] = $cells;
            $this->assertContains($method, ['自動テスト', '画面', '自動テスト＋画面', '手作業'], "{$number} の確認方法");
            $this->assertNotSame('', $procedure, "{$number} のテスト・手順が空");
            $this->assertNotSame('', $result, "{$number} の結果が空");
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date, "{$number} の確認日");
        }
    }

    public function test_summary_counts_match_the_tables(): void
    {
        $rows = ['FN' => '機能要件（FN-01〜18）', 'NF' => '非機能要件（NF-01〜07）', 'PG' => '画面（PG01〜25）'];
        foreach ($rows as $prefix => $label) {
            $counts = array_count_values($this->statuses($prefix));
            $expected = '| ' . $label . ' | ' . ($counts['完了'] ?? 0) . ' | ' . ($counts['一部対応'] ?? 0) . ' | ' . ($counts['未対応'] ?? 0) . ' | ' . ($counts['お試し環境の制約'] ?? 0) . ' |';
            $this->assertStringContainsString($expected, $this->doc(), "まとめの表の {$label} の件数");
        }
    }

    public function test_every_test_named_in_the_table_exists(): void
    {
        preg_match_all('/`(\w+Test)::(test_\w+)`/', $this->doc(), $matches, PREG_SET_ORDER);
        $this->assertGreaterThan(100, count($matches), '対応表にテストの名前が少なすぎる');

        $files = collect(glob(base_path('tests/{Feature,Feature/Auth,Unit}/*Test.php'), GLOB_BRACE))
            ->mapWithKeys(fn($path) => [basename($path, '.php') => file_get_contents($path)]);

        foreach ($matches as [, $class, $method]) {
            $this->assertTrue($files->has($class), "{$class} のファイルがない");
            $this->assertMatchesRegularExpression('/function ' . $method . '\(/', $files[$class], "{$class}::{$method} がない");
        }
    }
}
