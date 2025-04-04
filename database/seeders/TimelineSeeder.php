<?php

namespace Database\Seeders;

use App\Models\Timeline;
use Illuminate\Database\Seeder;

class TimelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timelines = [
            [
                'title'      => 'Welcome and free themed play in the playground',
                'duration'   => 30,
                'is_premium' => false,
            ],
            [
                'title'      => 'Themed team challenge play - with parents involved',
                'duration'   => 15,
                'is_premium' => false,
            ],
            [
                'title'      => 'Animation',
                'duration'   => 30,
                'is_premium' => false,
            ],
            [
                'title'      => 'Themed art craft',
                'duration'   => 30,
                'is_premium' => false,
            ],
            [
                'title'      => 'Themed dance and play',
                'duration'   => 15,
                'is_premium' => false,
            ],
            [
                'title'      => 'Themed theatre acting play',
                'duration'   => 15,
                'is_premium' => true,
            ],
            [
                'title'      => 'Animated theme games',
                'duration'   => 15,
                'is_premium' => true,
            ],
            [
                'title'      => 'Themed cooking master class',
                'duration'   => 15,
                'is_premium' => true,
            ],
            [
                'title'      => 'Food sharing and cake ceremony',
                'duration'   => 30,
                'is_premium' => false,
            ]
        ];

        foreach ($timelines as $timeline) {
            Timeline::create($timeline);
        }
    }
}
