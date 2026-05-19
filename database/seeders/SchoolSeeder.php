<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Level;
use App\Models\Period;
use App\Models\Rhythm;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Year;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // RHYTHMS
        // =========================
        $rhythms = ['Standard', 'Intensive', 'Weekend', 'Evening'];

        foreach ($rhythms as $index => $name) {
            Rhythm::updateOrCreate(
                ['name' => $name],
                ['id' => $index + 1] // safe only if table is empty after migrate:fresh
            );
        }

        // =========================
        // LEVELS (FIXED: sort_order)
        // =========================
        $levels = [
            ['name' => 'Maternelle', 'sort_order' => 1],
            ['name' => 'Primaire', 'sort_order' => 2],
            ['name' => 'Secondaire', 'sort_order' => 3],
            ['name' => 'Humanitaire', 'sort_order' => 4],
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(
                ['name' => $level['name']],
                ['sort_order' => $level['sort_order']]
            );
        }

        // =========================
        // ROOMS
        // =========================
        $rooms = ['Salle A', 'Salle B', 'Salle C', 'Salle Informatique'];

        foreach ($rooms as $room) {
            Room::updateOrCreate(['name' => $room]);
        }

        // =========================
        // YEAR
        // =========================
        $year = Year::firstOrCreate([
            'name' => now()->year,
        ]);

        // =========================
        // PERIODS (FIXED: sort_order)
        // =========================
        $period = Period::firstOrCreate(
            ['name' => 'Default'],
            [
                'year_id' => $year->id,
                'start' => now()->subDays(10),
                'end' => now()->addMonths(6),
                'sort_order' => 1,
            ]
        );

        // =========================
        // TEACHERS (PRO VERSION)
        // IMPORTANT: no "name" column exists
        // => we link to users
        // =========================
        $teacherNames = [
            'Jean Mbala',
            'Marie Kabuya',
            'Paul Kitenge',
            'Sarah Mbuyi',
        ];

        foreach ($teacherNames as $i => $name) {

            $user = User::firstOrCreate(
                ['email' => 'teacher'.($i + 1).'@academico.test'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                ]
            );

            // assign role if exists
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('teacher');
            }

            Teacher::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'hired_at' => now(),
                'max_week_hours' => 20,
            ]);
        }

        // =========================
        // COURSES (SAFE RELATIONS)
        // =========================
        $firstRhythm = Rhythm::first();
        $firstLevel = Level::first();
        $firstTeacher = Teacher::first();
        $firstRoom = Room::first();

        if ($firstRhythm && $firstLevel && $firstTeacher && $firstRoom) {

            Course::firstOrCreate(
                ['name' => 'Mathématiques'],
                [
                    'rhythm_id' => $firstRhythm->id,
                    'level_id' => $firstLevel->id,
                    'teacher_id' => $firstTeacher->id,
                    'room_id' => $firstRoom->id,
                    'period_id' => $period->id,
                    'price' => 100,
                    'volume' => 30,
                    'start_date' => now(),
                    'end_date' => now()->addMonths(3),
                ]
            );
        }
    }
}
