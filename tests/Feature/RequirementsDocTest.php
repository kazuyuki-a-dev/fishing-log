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

    /** 一覧の表の行「| [番号](#…) | 要件 | 優先度 | 状態 |」から、番号 => 状態 を取り出す */
    private function statuses(string $prefix): array
    {
        return array_map(fn(array $row) => $row['status'], $this->rows($prefix));
    }

    /** 一覧の表の行から、番号 => [優先度, 状態] を取り出す（#217） */
    private function rows(string $prefix): array
    {
        preg_match_all('/^\| \[(' . $prefix . '-?\d{2})\]\(#[a-z0-9-]+\) \| [^|]+ \| ([^|]+?) \| ([^|]+?) \|/mu', $this->doc(), $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn($m) => [$m[1] => ['priority' => trim($m[2]), 'status' => trim($m[3])]])->all();
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
        // 一覧の表の欄：番号 | 要件 | 優先度 | 状態 | 確認方法 | 結果 | 確認日
        preg_match_all('/^\| \[((?:FN-|NF-|PG)\d{2})\]\(#([a-z0-9-]+)\) \|(.*)\|$/mu', $this->doc(), $rows, PREG_SET_ORDER);
        $this->assertCount(50, $rows, 'FN 18・NF 7・PG 25 の行がない');

        foreach ($rows as [, $number, $anchor, $rest]) {
            $cells = array_map('trim', explode('|', $rest));
            $this->assertCount(6, $cells, "{$number} の欄の数");
            [, $priority, , $method, $result, $date] = $cells;
            $this->assertContains($priority, ['主役', '必須', '補助'], "{$number} の優先度");
            $this->assertSame(strtolower($number), $anchor, "{$number} のリンク先");
            $this->assertContains($method, ['自動テスト', '画面', '自動テスト＋画面', '手作業'], "{$number} の確認方法");
            $this->assertNotSame('', $result, "{$number} の結果が空");
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date, "{$number} の確認日");
        }
    }

    public function test_every_requirement_has_a_detail_section(): void
    {
        // 一覧の番号ごとに、詳しい内容（受け入れの条件・テスト・手順・結果）がある
        $sections = preg_split('/^### /m', $this->doc());
        $details = collect($sections)->mapWithKeys(fn($section) => [strtok($section, "\n") => $section]);

        foreach (['FN', 'NF', 'PG'] as $prefix) {
            foreach (array_keys($this->statuses($prefix)) as $number) {
                $this->assertTrue($details->has($number), "{$number} の詳しい内容がない");
                $body = $details[$number];
                $this->assertStringContainsString('受け入れの条件', $body, "{$number} に受け入れの条件がない");
                $this->assertMatchesRegularExpression('/^1\. \S/m', $body, "{$number} の受け入れの条件が空");
                $this->assertStringContainsString('テスト・手順', $body, "{$number} にテスト・手順がない");
                $this->assertMatchesRegularExpression('/^結果：\S/m', $body, "{$number} に結果がない");
            }
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

    public function test_priority_summary_matches_the_tables(): void
    {
        // 優先度ごとのまとめの件数が、一覧の表と合っている（#217）
        $rows = collect(['FN', 'NF', 'PG'])->flatMap(fn($prefix) => $this->rows($prefix));
        foreach (['主役', '必須', '補助'] as $priority) {
            $counts = array_count_values($rows->where('priority', $priority)->pluck('status')->all());
            $expected = '| ' . $priority . ' | ' . ($counts['完了'] ?? 0) . ' | ' . ($counts['一部対応'] ?? 0) . ' | ' . ($counts['未対応'] ?? 0) . ' | ' . ($counts['お試し環境の制約'] ?? 0) . ' |';
            $this->assertStringContainsString($expected, $this->doc(), "優先度ごとのまとめの {$priority} の件数");
        }
    }

    public function test_readme_priorities_match_the_table(): void
    {
        // README の「機能の優先順位と境界」の表の FN・NF が、対応表の優先度と同じ（#217）
        $readme = file_get_contents(base_path('README.md'));
        $section = str($readme)->after('## 機能の優先順位と境界')->before('## 主な機能')->toString();
        $table = array_merge($this->rows('FN'), $this->rows('NF'));
        $listed = [];
        foreach (['主役', '必須', '補助'] as $priority) {
            $this->assertSame(1, preg_match('/^\| ' . $priority . ' \|(.*)$/mu', $section, $line), "README に {$priority} の行がない");
            preg_match_all('/(?:FN|NF)-\d{2}/', $line[1], $numbers);
            foreach (array_unique($numbers[0]) as $number) {
                $this->assertSame($table[$number]['priority'], $priority, "{$number} の優先度が README と対応表で違う");
                $listed[] = $number;
            }
        }
        $this->assertEqualsCanonicalizing(array_keys($table), $listed, 'README の優先度の表に、FN・NF がすべて入っていない');
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
