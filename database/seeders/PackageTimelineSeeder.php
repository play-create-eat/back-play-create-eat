<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class PackageTimelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            1  => [
                'package_id'  => 1,
                'timeline_id' => 1,
            ],
            2  => [
                'package_id'  => 2,
                'timeline_id' => 1,
            ],
            3  => [
                'package_id'  => 1,
                'timeline_id' => 2,
            ],
            4  => [
                'package_id'  => 2,
                'timeline_id' => 2,
            ],
            5  => [
                'package_id'  => 1,
                'timeline_id' => 3,
            ],
            6  => [
                'package_id'  => 2,
                'timeline_id' => 3,
            ],
            7  => [
                'package_id'  => 1,
                'timeline_id' => 4,
            ],
            8  => [
                'package_id'  => 2,
                'timeline_id' => 4,
            ],
            9  => [
                'package_id'  => 1,
                'timeline_id' => 5,
            ],
            10 => [
                'package_id'  => 2,
                'timeline_id' => 5,
            ],
            11 => [
                'package_id'  => 2,
                'timeline_id' => 6,
            ],
            12 => [
                'package_id'  => 2,
                'timeline_id' => 7,
            ],
            13 => [
                'package_id'  => 2,
                'timeline_id' => 8,
            ],
            14 => [
                'package_id'  => 1,
                'timeline_id' => 9,
            ],
            15 => [
                'package_id'  => 2,
                'timeline_id' => 9,
            ],
        ];

        foreach ($data as $item) {
            DB::table('package_timeline')->insert([
                'package_id'  => $item['package_id'],
                'timeline_id' => $item['timeline_id'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
