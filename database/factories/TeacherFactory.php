<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        $specialty = fake()->randomElement([
            'Mathématiques',
            'Physique',
            'Informatique',
            'Histoire',
            'Langues',
        ]);

        return [
            'id' => User::factory()->state([
                'firstname' => fake()->firstName(),
                'lastname' => fake()->lastName().' - '.$specialty,
                'email' => fake()->unique()->safeEmail(),
            ]),
            'hired_at' => fake()->dateTimeBetween('-15 years', '-3 months'),
            'max_week_hours' => fake()->numberBetween(12, 35),
        ];
    }
}
