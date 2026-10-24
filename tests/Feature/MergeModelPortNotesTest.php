<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * モデル港の現地のメモをまとめるマイグレーション（#223）
 * シーダーが入れたままのメモだけを直し、利用者が書きかえたメモは変えない
 */
class MergeModelPortNotesTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = "港の中は立入禁止・釣り禁止の場所が多いです。釣りができる場所は、現地の表示や港の管理者の案内を確かめてください。\n国の「釣り文化振興モデル港」です（釣りができる場所：北防波堤）。開いている日や時間は、港の管理者の案内を確かめてください。";

    private function spotWithNote(string $note): Spot
    {
        $admin = User::factory()->admin()->create();

        return Spot::factory()->create(['facility_note' => $note, 'created_by' => $admin->id]);
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_10_24_120000_merge_model_port_notes.php'))->up();
    }

    public function test_seeded_note_is_merged_into_one_sentence(): void
    {
        $spot = $this->spotWithNote(self::OLD);
        $updatedAt = $spot->updated_at;

        $this->migrate();

        $spot->refresh();
        $this->assertSame('国の「釣り文化振興モデル港」です（釣りができる場所：北防波堤）。港の中は立入禁止・釣り禁止の場所が多いので、釣りができる場所と開いている日や時間は、現地の表示や港の管理者の案内を確かめてください。', $spot->facility_note);
        $this->assertSame(1, substr_count($spot->facility_note, '港の管理者の案内'));
        // だれかが直したように見せない
        $this->assertEquals($updatedAt, $spot->updated_at);
    }

    public function test_note_saved_again_from_the_form_is_also_merged(): void
    {
        // 入力欄から保存し直すと改行が \r\n になる。中身が同じなら直す
        $spot = $this->spotWithNote(str_replace("\n", "\r\n", self::OLD));

        $this->migrate();

        $this->assertSame(1, substr_count($spot->fresh()->facility_note, '港の管理者の案内'));
    }

    public function test_note_written_by_a_member_is_not_changed(): void
    {
        $note = self::OLD . "\n駐車場は朝6時から開きます。";
        $spot = $this->spotWithNote($note);

        $this->migrate();

        $this->assertSame($note, $spot->fresh()->facility_note);
    }
}
