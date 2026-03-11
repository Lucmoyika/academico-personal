<?php

namespace App\Listeners;

use App\Events\CourseUpdated;
use App\Models\Event;
use App\Traits\ReportsErrors;
use Illuminate\Support\Facades\DB;

class UpdateCourseEvents
{
    use ReportsErrors;

    public function handle(CourseUpdated $event): void
    {
        $course = $event->course;

        try {
            // If the course time itself has been modified, it is already handled by CourseTime model events

            // we need to update the events if the dates have changed
            if ($course->isDirty('start_date') || $course->isDirty('end_date')) {
                DB::transaction(function () use ($course): void {
                    Event::where('course_id', $course->id)->delete();

                    foreach ($course->times as $coursetime) {
                        $coursetime->createEvents();
                    }
                });
            }

            // update course events with new room, teacher and name
            Event::where('course_id', $course->id)->update(['room_id' => $course->room_id]);
            Event::where('course_id', $course->id)->update(['teacher_id' => $course->teacher_id]);
            Event::where('course_id', $course->id)->update(['name' => $course->name]);
        } catch (\Throwable $e) {
            $this->reportError($e, 'UpdateCourseEvents::handle', [
                'course_id' => $course->id,
            ]);
        }
    }
}
