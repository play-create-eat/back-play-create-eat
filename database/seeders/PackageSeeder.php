<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Package::query()->insert([
            [
                'name'                   => '5 star package',
                'weekday_price'          => 80.00,
                'weekend_price'          => 100.00,
                'min_children'           => 7,
                'duration_hours'         => 2,
                'cashback_percentage'    => 7,
                'bonus_playground_visit' => '3 hours',
                'order'                 => 1,
            ],
            [
                'name'                   => '5+ star package',
                'weekday_price'          => 100.00,
                'weekend_price'          => 120.00,
                'min_children'           => 10,
                'duration_hours'         => 3,
                'cashback_percentage'    => 10,
                'bonus_playground_visit' => '1 day',
                'order'                 => 2,
            ]
        ]);
    }
}
