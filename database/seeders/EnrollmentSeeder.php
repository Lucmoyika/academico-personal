<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeType;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'academico@thomasdebay.com')->first();

        $gradeTypes = collect([
            GradeType::firstOrCreate(['name' => 'Midterm'], ['total' => 20]),
            GradeType::firstOrCreate(['name' => 'Final'], ['total' => 20]),
            GradeType::firstOrCreate(['name' => 'Participation'], ['total' => 20]),
        ]);

        $courses = Course::query()->get();

        Student::query()->get()->each(function (Student $student) use ($courses, $admin, $gradeTypes): void {
            $courseIds = $courses->random(fake()->numberBetween(2, min(5, $courses->count())))->pluck('id')->unique();

            foreach ($courseIds as $courseId) {
                $enrollment = Enrollment::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $courseId,
                    ],
                    [
                        'status_id' => fake()->randomElement([1, 2, 2]),
                        'responsible_id' => $admin?->id,
                    ]
                );

                foreach ($gradeTypes as $gradeType) {
                    Grade::firstOrCreate(
                        [
                            'enrollment_id' => $enrollment->id,
                            'grade_type_id' => $gradeType->id,
                        ],
                        ['grade' => fake()->randomFloat(2, 8, 20)]
                    );
                }

                Result::firstOrCreate(
                    ['enrollment_id' => $enrollment->id],
                    ['result_type_id' => fake()->randomElement([1, 2, 3])]
                );
            }
        });
    }
}
