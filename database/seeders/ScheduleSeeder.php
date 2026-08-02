<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            ['day_of_week' => 1, 'is_working' => true,  'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 2, 'is_working' => true,  'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 3, 'is_working' => true,  'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 4, 'is_working' => true,  'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 5, 'is_working' => true,  'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 6, 'is_working' => false, 'start_time' => null,       'end_time' => null],
            ['day_of_week' => 0, 'is_working' => false, 'start_time' => null,       'end_time' => null],
        ];

        foreach ($days as $day) {
            DB::table('schedules')->updateOrInsert(
                ['day_of_week' => $day['day_of_week']],
                array_merge($day, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
