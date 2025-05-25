<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Timeline;

class TimelineSeeder extends Seeder
{
    public function run(): void
    {
        $timelines = [
            [
                'id' => 1,
                'title' => 'Welcome and free play in the playground',
                'duration' => 30,
                'package_id' => 1,
            ],
            [
                'id' => 2,
                'title' => 'Team challenge play ',
                'duration' => 15,
                'package_id' => 1,
            ],
            [
                'id' => 3,
                'title' => 'Themed Games',
                'duration' => 30,
                'package_id' => 1,
            ],
            [
                'id' => 5,
                'title' => 'Themed dancing and singing ',
                'duration' => 15,
                'package_id' => 1,
            ],
            [
                'id' => 6,
                'title' => 'Food and cake ceremony',
                'duration' => 30,
                'package_id' => 1,
            ],
            [
                'id' => 7,
                'title' => 'Welcome and free play in the playground',
                'duration' => 30,
                'package_id' => 2,
            ],
            [
                'id' => 8,
                'title' => 'Team challenge play - with parents involved',
                'duration' => 15,
                'package_id' => 2,
            ],
            [
                'id' => 9,
                'title' => 'Themed art craft',
                'duration' => 15,
                'package_id' => 2,
            ],
            [
                'id' => 10,
                'title' => 'Themed dancing and singing',
                'duration' => 15,
                'package_id' => 2,
            ],
            [
                'id' => 11,
                'title' => 'Mascot Parade ',
                'duration' => 10,
                'package_id' => 2,
            ],
            [
                'id' => 12,
                'title' => 'Puppet Show ( 1-3 years ) / Magician show ( > 3 years )',
                'duration' => 20,
                'package_id' => 2,
            ],
            [
                'id' => 14,
                'title' => 'Themed Games',
                'duration' => 15,
                'package_id' => 2,
            ],
            [
                'id' => 15,
                'title' => 'Food & Cake & Pinata ceremony',
                'duration' => 30,
                'package_id' => 2,
            ],
        ];

        foreach ($timelines as $timeline) {
            Timeline::updateOrCreate(['id' => $timeline['id']], $timeline);
        }
    }
}
