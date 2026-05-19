<?php

namespace Database\Seeders;

use App\Models\PhoneNumber;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        Student::factory()->count(50)->create()->each(function (Student $student): void {
            PhoneNumber::updateOrCreate(
                [
                    'phoneable_type' => Student::class,
                    'phoneable_id' => $student->id,
                ],
                ['phone_number' => fake()->phoneNumber()]
            );
        });
    }
}
