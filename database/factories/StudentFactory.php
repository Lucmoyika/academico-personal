<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'id' => User::factory()->state([
                'firstname' => fake()->firstName(),
                'lastname' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
            ]),
            'idnumber' => fake()->unique()->numerify('STD-########'),
            'address' => fake()->address(),
            'birthdate' => fake()->dateTimeBetween('-30 years', '-8 years')->format('Y-m-d'),
            'gender_id' => fake()->randomElement([1, 2]),
        ];
    }
}
