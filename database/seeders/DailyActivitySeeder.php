<?php

namespace Database\Seeders;

use App\Models\DailyActivity;
use Illuminate\Database\Seeder;

class DailyActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activities = [
            [
                'name' => 'Baby&Toddler Time!',
                'description' => '',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'location' => 'All zones',
                'category' => 'Baby & Toddler',
                'color' => '#FFB6C1',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 1,
            ],
            [
                'name' => 'Baby&Toddler Sing and Dance',
                'description' => 'Clap Snap with Mommy and Daddy',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'location' => 'Disco room',
                'category' => 'Music & Dance',
                'color' => '#87CEEB',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 2,
            ],
            [
                'name' => 'Messy Play',
                'description' => 'Explore and make a mess',
                'start_time' => '10:15',
                'end_time' => '12:15',
                'location' => 'Create Zone',
                'category' => 'Creative Play',
                'color' => '#90EE90',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 3,
            ],
            [
                'name' => 'Story time',
                'description' => 'Act and play like a story character',
                'start_time' => '10:30',
                'end_time' => '12:30',
                'location' => 'All Zone',
                'category' => 'Story Time',
                'color' => '#FFB6C1',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 4,
            ],
            [
                'name' => 'Little Jungle Play',
                'description' => 'Dress up like a jungle animal and learn the animal sounds with us',
                'start_time' => '10:45',
                'end_time' => '12:45',
                'location' => 'Disco room',
                'category' => 'Educational',
                'color' => '#DDA0DD',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 5,
            ],
            [
                'name' => 'Bubble Time',
                'description' => 'Say "Hi" to Super Roy!',
                'start_time' => '11:00',
                'end_time' => '12:00',
                'location' => 'Disco room',
                'category' => 'Physical Activity',
                'color' => '#F0E68C',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 6,
            ],
            [
                'name' => 'Sing, Shake and Sway',
                'description' => 'Lets wiggle and giggle',
                'start_time' => '03:00',
                'end_time' => '04:00',
                'location' => 'Play Zone',
                'category' => 'Music & Dance',
                'color' => '#87CEEB',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 7,
            ],
            [
                'name' => 'Fluppy Slime with Mom and Dad',
                'description' => 'Enjoy the messy-friendly experience with Slime',
                'start_time' => '04:00',
                'end_time' => '05:00',
                'location' => 'Create Zone',
                'category' => 'Creative Play',
                'color' => '#90EE90',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 8,
            ],
            [
                'name' => 'Scavenger Hunt',
                'description' => 'Get ready for a fun treasure hunt',
                'start_time' => '04:30',
                'end_time' => '05:30',
                'location' => 'Play Zone',
                'category' => 'Physical Activity',
                'color' => '#FFB6C1',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 9,
            ],
            [
                'name' => 'Sing, Shake and Sway',
                'description' => 'Lets wiggle and giggle',
                'start_time' => '05:00',
                'end_time' => '06:00',
                'location' => 'Play Zone',
                'category' => 'Music & Dance',
                'color' => '#87CEEB',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 10,
            ],
            [
                'name' => 'Little Movie Picnic',
                'description' => 'Experience a cozy and fun snack time movie',
                'start_time' => '05:30',
                'end_time' => '06:30',
                'location' => 'Disco Room',
                'category' => 'Other',
                'color' => '#87CEEB',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 11,
            ],
            [
                'name' => 'Super Power Play Competition',
                'description' => 'Team up challenge',
                'start_time' => '06:00',
                'end_time' => '07:00',
                'location' => 'Play zone',
                'category' => 'Competition',
                'color' => '#90EE90',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 12,
            ],
            [
                'name' => 'Bubble Party',
                'description' => 'Cheer for the experience!',
                'start_time' => '06:30',
                'end_time' => '07:30',
                'location' => 'Reception',
                'category' => 'Party',
                'color' => '#FFB6C1',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 13,
            ],
            [
                'name' => 'Play Create Eat Club Dance with Games',
                'description' => 'Dance with us!',
                'start_time' => '07:00',
                'end_time' => '08:00',
                'location' => 'Disco room',
                'category' => 'Music & Dance',
                'color' => '#87CEEB',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'order' => 14,
            ],
        ];

        foreach ($activities as $activity) {
            DailyActivity::create($activity);
        }
    }
}
