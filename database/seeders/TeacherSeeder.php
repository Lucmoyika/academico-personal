<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $specialties = collect([
            'Mathematics',
            'Physics',
            'Chemistry',
            'Biology',
            'Literature',
            'History',
            'Geography',
            'Computer Science',
            'Economics',
            'Languages',
        ]);

        $specialties->each(function (string $specialty, int $index): void {
            $user = User::firstOrCreate(
                ['email' => sprintf('teacher%02d@academico.test', $index + 1)],
                [
                    'username' => sprintf('teacher%02d', $index + 1),
                    'firstname' => fake()->firstName(),
                    'lastname' => fake()->lastName().' - '.$specialty,
                    'password' => Hash::make('secret'),
                    'locale' => fake()->randomElement(['fr', 'en', 'es']),
                ]
            );

            Teacher::firstOrCreate(
                ['id' => $user->id],
                [
                    'hired_at' => now()->subDays(fake()->numberBetween(30, 2000)),
                    'max_week_hours' => fake()->numberBetween(12, 30),
                ]
            );
        });
    }
}
