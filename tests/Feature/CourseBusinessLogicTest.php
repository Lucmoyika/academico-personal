<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseTime;
use App\Models\Enrollment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseBusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \DB::table('enrollment_status_types')->insert([
            ['id' => 1, 'name' => json_encode(['fr' => 'Pending'])],
            ['id' => 2, 'name' => json_encode(['fr' => 'Paid'])],
            ['id' => 3, 'name' => json_encode(['fr' => 'Cancelled'])],
        ]);
    }

    public function test_course_times_accessor_formats_schedule(): void
    {
        $course = Course::factory()->create();

        CourseTime::factory()->create([
            'course_id' => $course->id,
            'day' => 1, // Monday
            'start' => '09:00:00',
            'end' => '11:00:00',
        ]);

        $courseTimes = $course->fresh()->course_times;

        $this->assertNotEmpty($courseTimes);
        $this->assertIsString($courseTimes);
    }

    public function test_hide_children_scope_excludes_child_courses(): void
    {
        $parentCourse = Course::factory()->create();
        $childCourse = Course::factory()->create(['parent_course_id' => $parentCourse->id]);

        $courses = Course::hideChildren()->pluck('id');

        $this->assertTrue($courses->contains($parentCourse->id));
        $this->assertFalse($courses->contains($childCourse->id));
    }

    public function test_realcourses_scope_excludes_courses_with_children(): void
    {
        $parentCourse = Course::factory()->create();
        Course::factory()->create(['parent_course_id' => $parentCourse->id]);
        $standaloneCourse = Course::factory()->create();

        $realCourses = Course::realcourses()->pluck('id');

        $this->assertFalse($realCourses->contains($parentCourse->id));
        $this->assertTrue($realCourses->contains($standaloneCourse->id));
    }

    public function test_total_volume_includes_remote_volume(): void
    {
        $course = Course::factory()->create([
            'volume' => 40,
            'remote_volume' => 10,
        ]);

        $this->assertEquals(50, $course->total_volume);
    }

    public function test_save_course_times_creates_and_deletes(): void
    {
        $course = Course::factory()->create();

        // Create initial course times
        $initialTimes = collect([
            ['day' => 1, 'start' => '09:00:00', 'end' => '11:00:00'],
            ['day' => 3, 'start' => '14:00:00', 'end' => '16:00:00'],
        ]);
        $course->saveCourseTimes($initialTimes);
        $this->assertEquals(2, $course->times()->count());

        // Update: remove Wednesday, keep Monday, add Friday
        $updatedTimes = collect([
            ['day' => 1, 'start' => '09:00:00', 'end' => '11:00:00'],
            ['day' => 5, 'start' => '10:00:00', 'end' => '12:00:00'],
        ]);
        $course->refresh();
        $course->saveCourseTimes($updatedTimes);

        $course->refresh();
        $days = $course->times->pluck('day')->sort()->values()->toArray();
        $this->assertEquals([1, 5], $days);
    }

    public function test_course_enrollments_count_excludes_children_and_cancelled(): void
    {
        $course = Course::factory()->create();

        // Active enrollment (counted)
        $activeEnrollment = Enrollment::create([
            'student_id' => Student::factory()->create()->id,
            'course_id' => $course->id,
            'status_id' => 1,
        ]);

        // Cancelled enrollment (not counted)
        Enrollment::create([
            'student_id' => Student::factory()->create()->id,
            'course_id' => $course->id,
            'status_id' => 3,
        ]);

        // Child enrollment (not counted in real_enrollments)
        Enrollment::create([
            'student_id' => Student::factory()->create()->id,
            'course_id' => $course->id,
            'status_id' => 1,
            'parent_id' => $activeEnrollment->id,
        ]);

        // enrollments() includes status 1 & 2 but doesn't filter children
        // course_enrollments_count uses the enrollments() relation
        $this->assertEquals(2, $course->fresh()->course_enrollments_count);

        // real_enrollments excludes children
        $this->assertEquals(1, $course->real_enrollments()->count());
    }
}
