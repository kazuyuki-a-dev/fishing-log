<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 自分でログインして確かめるためのユーザー（パスワードは password）
        $me = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'home_prefecture' => '秋田県',
        ]);

        // ほかの釣り人3人（メインフィールドは同じ県）
        $users = User::factory(3)->create(['home_prefecture' => '秋田県'])->push($me);

        // 同じ県の釣り場8件（名前は架空のもの）
        $spotNames = [
            '北港 第一堤防',
            '南浜サーフ',
            '旧河口',
            '岬の磯',
            '東漁港',
            '西防波堤',
            '運河筋',
            '新港 岸壁',
        ];

        $spots = collect($spotNames)->map(fn($name) => Spot::factory()->create([
            'name' => $name,
            'prefecture' => '秋田県',
            'latitude' => fake()->randomFloat(7, 39.3, 40.1),
            'longitude' => fake()->randomFloat(7, 139.8, 140.1),
            'created_by' => $users->random()->id,
        ]));

        // 釣行50件。3割くらいは坊主（釣果0件）、残りは1〜4匹
        Trip::factory(50)
            ->recycle($users)
            ->recycle($spots)
            ->create()
            ->each(function (Trip $trip) {
                if (fake()->boolean(30)) {
                    return; // 坊主
                }
                FishCatch::factory(fake()->numberBetween(1, 4))->for($trip)->create();
            });
    }
}
