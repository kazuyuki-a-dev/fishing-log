<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 決めたことの記録（docs/decisions.md）の表の形が、くずれていないか（#227）
 * 2つの行が1行につながると、GitHub の画面で表がくずれて見える
 */
class DecisionsDocTest extends TestCase
{
    public function test_every_decision_row_has_five_columns(): void
    {
        $lines = file(base_path('docs/decisions.md'), FILE_IGNORE_NEW_LINES);
        $rows = array_filter($lines, fn(string $line) => str_starts_with($line, '| 20'));
        $this->assertGreaterThan(200, count($rows), '決めたことの行が少なすぎる');

        foreach ($rows as $number => $line) {
            // 日付・項目・定義書・決定・理由の5つ（区切りの「|」は6つ）
            $this->assertSame(6, substr_count($line, '|'), ($number + 1) . ' 行目の列の数（2つの行がつながっていないか）');
            $this->assertMatchesRegularExpression('/^\| \d{4}-\d{2}-\d{2} \|/', $line, ($number + 1) . ' 行目の日付');
        }
    }
}
