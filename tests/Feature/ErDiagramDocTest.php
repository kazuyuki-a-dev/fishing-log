<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * README の ER 図（Mermaid）が、実際のテーブルと合っているか（#200）
 * テーブルを足して図を直し忘れたら、ここで気づけるようにする
 */
class ErDiagramDocTest extends TestCase
{
    use RefreshDatabase;

    /** Laravel が最初から作る、アプリの設計には入れないテーブル */
    private const FRAMEWORK_TABLES = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    /** README の ```mermaid の中身 */
    private function diagram(): string
    {
        $readme = file_get_contents(base_path('README.md'));
        $this->assertSame(1, preg_match('/```mermaid\n(erDiagram\n.*?)```/s', $readme, $matches), 'README に ER 図（Mermaid）がない');

        return $matches[1];
    }

    /** 図にある表の名前（「    users {」の行） */
    private function entities(): array
    {
        preg_match_all('/^\s+(\w+) \{$/m', $this->diagram(), $matches);

        return $matches[1];
    }

    public function test_every_app_table_is_in_the_diagram(): void
    {
        // 同じ MySQL に開発用のデータベースもあるので、テスト用のデータベースの表だけを見る
        $tables = collect(Schema::getTables())
            ->where('schema', DB::connection()->getDatabaseName())
            ->pluck('name')->diff(self::FRAMEWORK_TABLES)->sort()->values()->all();
        $this->assertContains('spot_local_info_changes', $tables);

        $entities = $this->entities();
        sort($entities);
        $this->assertSame($tables, $entities, 'データベースのテーブルと README の ER 図の表が合わない');
    }

    public function test_every_relationship_uses_tables_in_the_diagram(): void
    {
        preg_match_all('/^\s+(\w+) [|}o.\-{]+ (\w+) : /m', $this->diagram(), $matches, PREG_SET_ORDER);
        $this->assertNotEmpty($matches, 'ER 図につながりの線がない');

        $entities = $this->entities();
        foreach ($matches as [, $left, $right]) {
            $this->assertContains($left, $entities, "つながりの {$left} が図の表にない");
            $this->assertContains($right, $entities, "つながりの {$right} が図の表にない");
        }
    }

    public function test_foreign_key_columns_in_the_diagram_exist(): void
    {
        // 図で FK と書いた列が、本当にその表にある
        $current = null;
        foreach (explode("\n", $this->diagram()) as $line) {
            if (preg_match('/^\s+(\w+) \{$/', $line, $m)) {
                $current = $m[1];
            } elseif ($current && preg_match('/^\s+\w+ (\w+) FK/', $line, $m)) {
                $this->assertTrue(Schema::hasColumn($current, $m[1]), "{$current}.{$m[1]} がない");
            }
        }
    }
}
