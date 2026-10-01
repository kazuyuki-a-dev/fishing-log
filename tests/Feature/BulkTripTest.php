<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 天気の API には本当に取りに行かない
        Http::fake();
    }

    /**
     * 自分の釣り場を1つ作る（位置なし＝天気は取りに行かない）
     */
    private function spotOf(User $user, array $overrides = []): Spot
    {
        return Spot::factory()->create(array_merge([
            'created_by' => $user->id,
            'visibility' => 'private',
            'latitude' => null,
            'longitude' => null,
        ], $overrides));
    }

    /**
     * 1行分の入力（釣った魚1匹）
     */
    private function row(Spot $spot, array $overrides = []): array
    {
        return array_merge([
            'went_at' => '2026-05-03T06:00',
            'spot_id' => $spot->id,
            'time_of_day' => config('fishing.times_of_day')[0],
            'fish_species' => config('fishing.fish_species')[0],
            'method' => config('fishing.methods')[0],
            'length_cm' => 20,
        ], $overrides);
    }

    public function test_bulk_mode_shows_the_bulk_form(): void
    {
        $user = User::factory()->create();
        $this->spotOf($user);

        $this->actingAs($user)
            ->get(route('trips.create', ['mode' => 'bulk']))
            ->assertOk()
            ->assertSee('過去の釣行をまとめて登録')
            ->assertSee('bulkRows', false);
    }

    public function test_normal_form_has_a_link_to_bulk_mode(): void
    {
        $user = User::factory()->create();
        $this->spotOf($user);

        $this->actingAs($user)
            ->get(route('trips.create'))
            ->assertOk()
            ->assertSee('昔の釣行をまとめて登録する');
    }

    public function test_rows_on_the_same_day_spot_and_time_become_one_trip(): void
    {
        $user = User::factory()->create();
        $spot = $this->spotOf($user);

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'public',
            'rows' => [
                $this->row($spot, ['went_at' => '2026-05-03T07:30']),
                $this->row($spot, ['went_at' => '2026-05-03T06:00']),
                $this->row($spot, ['went_at' => '2026-05-04T06:00']),
            ],
        ])
            ->assertRedirect(route('trips.index'))
            ->assertSessionHas('status', '2件の釣行を登録しました（釣果 3 匹）。');

        $trips = $user->trips()->withCount('catches')->orderBy('went_at')->get();
        $this->assertSame([2, 1], $trips->pluck('catches_count')->all());
        // 釣行の日時は、まとまった行のうち一番早いもの
        $this->assertSame('2026-05-03 06:00', $trips[0]->went_at->format('Y-m-d H:i'));
        // 公開範囲は全部に同じものが入る
        $this->assertSame(['public', 'public'], $trips->pluck('visibility')->all());
        // 潮は自動で入る
        $this->assertNotNull($trips[0]->tide);
    }

    public function test_different_time_of_day_makes_another_trip(): void
    {
        $user = User::factory()->create();
        $spot = $this->spotOf($user);
        $times = config('fishing.times_of_day');

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [
                $this->row($spot, ['time_of_day' => $times[0]]),
                $this->row($spot, ['time_of_day' => $times[1]]),
            ],
        ]);

        $this->assertSame(2, $user->trips()->count());
    }

    public function test_row_without_species_is_saved_as_bozu(): void
    {
        $user = User::factory()->create();
        $spot = $this->spotOf($user);

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [
                // 坊主だけの日
                $this->row($spot, ['went_at' => '2026-05-03T06:00', 'fish_species' => '', 'method' => '']),
                // 釣れた行と坊主の行が同じ釣行にまとまる日
                $this->row($spot, ['went_at' => '2026-05-04T06:00']),
                $this->row($spot, ['went_at' => '2026-05-04T06:30', 'fish_species' => '', 'method' => '']),
            ],
        ])->assertSessionHas('status', '2件の釣行を登録しました（釣果 1 匹）。');

        $trips = $user->trips()->withCount('catches')->orderBy('went_at')->get();
        $this->assertSame([0, 1], $trips->pluck('catches_count')->all());
    }

    public function test_others_private_spot_cannot_be_chosen(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $othersSpot = $this->spotOf($other);

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [$this->row($othersSpot)],
        ])->assertSessionHasErrors('rows.0.spot_id');

        $this->assertSame(0, $user->trips()->count());
    }

    public function test_others_public_spot_can_be_chosen(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $publicSpot = $this->spotOf($other, ['visibility' => 'public']);

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [$this->row($publicSpot)],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $user->trips()->count());
    }

    public function test_method_is_required_when_species_is_chosen(): void
    {
        $user = User::factory()->create();
        $spot = $this->spotOf($user);

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [$this->row($spot, ['method' => ''])],
        ])->assertSessionHasErrors('rows.0.method');
    }

    public function test_up_to_20_rows_can_be_sent_at_once(): void
    {
        $user = User::factory()->create();
        $spot = $this->spotOf($user);

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => array_fill(0, 21, $this->row($spot)),
        ])->assertSessionHasErrors('rows');

        $this->assertSame(0, $user->trips()->count());
    }

    public function test_at_least_one_row_is_needed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [],
        ])->assertSessionHasErrors('rows');
    }

    public function test_photo_is_saved_with_the_catch(): void
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::fake('public');
        $user = User::factory()->create();
        $spot = $this->spotOf($user);

        $this->actingAs($user)->post(route('trips.bulk-store'), [
            'visibility' => 'private',
            'rows' => [
                $this->row($spot, ['photo' => UploadedFile::fake()->image('fish.jpg', 800, 600)]),
            ],
        ])->assertSessionHasNoErrors();

        $catch = $user->trips()->first()->catches()->first();
        $this->assertNotNull($catch->image_path);
        $disk->assertExists($catch->image_path);
    }

    public function test_new_user_sees_the_bulk_button_on_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('昔の釣行をまとめて登録する')
            ->assertSee(route('trips.create', ['mode' => 'bulk']), false);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->post(route('trips.bulk-store'), ['visibility' => 'private', 'rows' => []])
            ->assertRedirect(route('login'));
    }
}
