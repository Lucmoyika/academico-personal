<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Course;
use App\Models\Level;
use App\Models\Period;
use App\Models\Rhythm;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\Year;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $internalCampus = Campus::firstOrCreate(
            ['id' => 1],
            ['name' => ['en' => 'Internal', 'es' => 'Interno', 'fr' => 'Interne']]
        );

        $externalCampus = Campus::firstOrCreate(
            ['id' => 2],
            ['name' => ['en' => 'External', 'es' => 'Externo', 'fr' => 'Externe']]
        );

        $rhythms = collect(['Standard', 'Intensive', 'Evening', 'Weekend'])
            ->map(fn (string $name) => Rhythm::firstOrCreate(['name' => $name]));

        $levels = collect(['Beginner', 'Intermediate', 'Advanced', 'Expert'])
            ->map(fn (string $name, int $index) => Level::firstOrCreate(['name' => $name], ['sort_order' => $index + 1]));

        $rooms = collect([
            Room::firstOrCreate(['name' => 'Room A1', 'campus_id' => $internalCampus->id]),
            Room::firstOrCreate(['name' => 'Room B1', 'campus_id' => $internalCampus->id]),
            Room::firstOrCreate(['name' => 'Computer Lab', 'campus_id' => $internalCampus->id]),
            Room::firstOrCreate(['name' => 'Room E1', 'campus_id' => $externalCampus->id]),
        ]);

        $year = Year::firstOrCreate(['name' => (string) now()->year]);

        $period = Period::firstOrCreate(
            ['name' => 'Default', 'year_id' => $year->id],
            [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->addMonths(4)->endOfMonth()->toDateString(),
                'order' => 1,
            ]
        );

        $teachers = Teacher::query()->inRandomOrder()->take(10)->get();

        collect(range(1, 20))->each(function (int $index) use ($internalCampus, $externalCampus, $rhythms, $levels, $rooms, $teachers, $period): void {
            $level = $levels->random();
            $rhythm = $rhythms->random();
            $teacher = $teachers->get(($index - 1) % max(1, $teachers->count()));
            $room = $rooms->random();

            Course::factory()->create([
                'name' => sprintf('Course %02d - %s', $index, $level->name),
                'campus_id' => $index <= 16 ? $internalCampus->id : $externalCampus->id,
                'rhythm_id' => $rhythm->id,
                'level_id' => $level->id,
                'teacher_id' => $teacher?->id,
                'room_id' => $room->id,
                'period_id' => $period->id,
                'volume' => fake()->randomElement([20, 30, 40, 60]),
                'price' => fake()->randomElement([12000, 15000, 18000, 22000]),
                'spots' => fake()->numberBetween(15, 30),
                'start_date' => now()->subDays(fake()->numberBetween(5, 20))->toDateString(),
                'end_date' => now()->addDays(fake()->numberBetween(45, 120))->toDateString(),
            ]);
        });
    }
}
