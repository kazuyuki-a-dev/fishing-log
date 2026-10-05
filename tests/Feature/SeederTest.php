<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\TideCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_real_spots_are_in_every_prefecture_except_gifu(): void
    {
        // 海のある県には港、海のない県には湖。岐阜県は、釣りで有名な湖を確かめられなかったので0か所（#106）
        $prefectures = collect(config('prefectures'))->reject(fn($prefecture) => $prefecture === '岐阜県');
        foreach ($prefectures as $prefecture) {
            $this->assertGreaterThan(0, Spot::where('prefecture', $prefecture)->count(), $prefecture);
        }
        $this->assertSame(0, Spot::where('prefecture', '岐阜県')->count());
        $this->assertSame(count(require database_path('seeders/data/real_spots.php')), Spot::count());
    }

    public function test_real_spots_are_public_and_registered_by_admin(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertSame(Spot::count(), Spot::where('visibility', 'public')->where('created_by', $admin->id)->count());

        // 位置があるものは日本の範囲に入っている。位置がない（名前だけの）釣り場もある
        foreach (Spot::whereNotNull('latitude')->get() as $spot) {
            $this->assertTrue($spot->latitude >= 20 && $spot->latitude <= 46 && $spot->longitude >= 122 && $spot->longitude <= 154, $spot->name);
        }
        $this->assertGreaterThan(0, Spot::whereNull('latitude')->count());
    }

    public function test_ports_warn_that_fishing_is_often_prohibited(): void
    {
        // 港も湖も「注意あり」で、決まりを確かめるメモがある（NF-04）
        $this->assertSame(Spot::count(), Spot::where('caution_type', '注意あり')->count());
        $akita = Spot::where('name', '秋田港')->first();
        $this->assertStringContainsString('立入禁止・釣り禁止', $akita->facility_note);
        $this->assertStringContainsString('釣り文化振興モデル港', $akita->facility_note);
        $this->assertStringContainsString('北防波堤', $akita->facility_note);
        $this->assertStringContainsString('遊漁券', Spot::where('name', '中禅寺湖')->first()->facility_note);
    }

    public function test_dummy_trips_are_only_at_model_port_in_akita(): void
    {
        // ダミーの釣行は、秋田県のモデル港（秋田港）にだけ。ふつうの港と湖には付けない
        $this->assertSame(50, Trip::count());
        $this->assertSame(['秋田港'], Trip::with('spot')->get()->pluck('spot.name')->unique()->values()->all());
        $this->assertSame(4, User::where('home_prefecture', '秋田県')->count());
    }

    public function test_user_in_another_prefecture_has_no_trips(): void
    {
        // 神奈川県にはモデル港がないので、県の切り替えを確かめる人の釣行はない
        $visitor = User::where('email', 'wanoku@example.com')->first();
        $this->assertSame('神奈川県', $visitor->home_prefecture);
        $this->assertSame(0, $visitor->trips()->count());
    }

    public function test_emails_are_example_com(): void
    {
        $this->assertTrue(User::pluck('email')->every(fn($email) => str_ends_with($email, '@example.com')));
    }

    public function test_admin_is_only_admin_example_com(): void
    {
        // 管理者は1人だけ（NF-04）。釣行は持たない
        $this->assertSame(['admin@example.com'], User::where('role', 'admin')->pluck('email')->all());
        $this->assertSame(0, User::where('email', 'admin@example.com')->first()->trips()->count());
    }

    public function test_test_user_has_enough_trips(): void
    {
        $me = User::where('email', 'test@example.com')->first();
        $this->assertSame(15, $me->trips()->count());
    }

    public function test_all_visibilities_are_mixed(): void
    {
        $this->assertEqualsCanonicalizing(['public', 'spot_hidden', 'private'], Trip::distinct()->pluck('visibility')->all());
    }

    public function test_some_trips_are_bozu(): void
    {
        $bozu = Trip::doesntHave('catches')->count();
        $this->assertGreaterThan(0, $bozu);
        $this->assertLessThan(Trip::count(), $bozu);
    }

    public function test_tide_matches_the_date(): void
    {
        $tides = new TideCalculator();
        foreach (Trip::all() as $trip) {
            $this->assertSame($tides->tideFor(Carbon::parse($trip->went_at)), $trip->tide);
        }
    }

    public function test_trips_are_in_the_past(): void
    {
        $this->assertSame(0, Trip::where('went_at', '>', now())->count());
    }
}
