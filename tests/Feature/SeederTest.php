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

    public function test_main_data_is_gathered_in_akita(): void
    {
        $this->assertSame(4, User::where('home_prefecture', '秋田県')->count());
        $this->assertSame(8, Spot::where('prefecture', '秋田県')->count());
        $this->assertSame(50, Trip::whereHas('spot', fn($query) => $query->where('prefecture', '秋田県'))->count());
    }

    public function test_a_little_data_in_another_prefecture(): void
    {
        $this->assertSame(1, User::where('home_prefecture', '神奈川県')->count());
        $this->assertSame(2, Spot::where('prefecture', '神奈川県')->count());
        $this->assertSame(6, Trip::whereHas('spot', fn($query) => $query->where('prefecture', '神奈川県'))->count());

        // ほかの人の画面に出るように、非公開の釣行はない
        $this->assertSame(0, Trip::where('visibility', 'private')
            ->whereHas('spot', fn($query) => $query->where('prefecture', '神奈川県'))->count());
    }

    public function test_emails_are_example_com(): void
    {
        $this->assertTrue(User::pluck('email')->every(fn($email) => str_ends_with($email, '@example.com')));
    }

    public function test_test_user_has_enough_trips(): void
    {
        $me = User::where('email', 'test@example.com')->first();
        $this->assertSame(15, $me->trips()->count());
    }

    public function test_all_visibilities_are_mixed(): void
    {
        $this->assertEqualsCanonicalizing(['public', 'spot_hidden', 'private'], Trip::distinct()->pluck('visibility')->all());
        $this->assertEqualsCanonicalizing(['public', 'private'], Spot::distinct()->pluck('visibility')->all());
    }

    public function test_some_trips_are_bozu(): void
    {
        $bozu = Trip::doesntHave('catches')->count();
        $this->assertGreaterThan(0, $bozu);
        $this->assertLessThan(Trip::count(), $bozu);
    }

    public function test_no_trip_is_on_others_private_spot(): void
    {
        $wrong = Trip::with('spot')->get()->filter(
            fn(Trip $trip) => $trip->spot->visibility === 'private' && $trip->spot->created_by !== $trip->user_id
        );
        $this->assertCount(0, $wrong);
    }

    public function test_tide_matches_the_date_and_spots_have_location(): void
    {
        $tides = new TideCalculator();
        foreach (Trip::all() as $trip) {
            $this->assertSame($tides->tideFor(Carbon::parse($trip->went_at)), $trip->tide);
        }
        $this->assertSame(0, Spot::whereNull('latitude')->count());
    }

    public function test_trips_are_in_the_past(): void
    {
        $this->assertSame(0, Trip::where('went_at', '>', now())->count());
    }
}
