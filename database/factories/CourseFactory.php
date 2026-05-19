<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Course;
use App\Models\Level;
use App\Models\Period;
use App\Models\Rhythm;
use App\Models\Room;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+2 weeks');
        $durationHours = fake()->randomElement([20, 30, 40, 60]);

        return [
            'name' => fake()->randomElement(['Français', 'Mathématiques', 'Sciences', 'Informatique']).' '.fake()->randomElement(['A1', 'A2', 'B1', 'B2']).' - '.fake()->numberBetween(1, 99),
            'campus_id' => Campus::factory(),
            'rhythm_id' => Rhythm::factory(),
            'level_id' => Level::factory(),
            'volume' => $durationHours,
            'price' => fake()->randomElement([12000, 15000, 18000, 22000]),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween($startDate, '+5 months')->format('Y-m-d'),
            'room_id' => Room::factory(),
            'teacher_id' => Teacher::factory(),
            'parent_course_id' => null,
            'exempt_attendance' => false,
            'period_id' => Period::factory(),
            'spots' => fake()->numberBetween(12, 35),
        ];
    }
}
