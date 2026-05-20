<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class HrDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 520;

    protected static string $view = 'filament.pages.hr-dashboard';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->can('hr.view') ?? false;
    }

    public ?int $selectedPeriodId = null;
    public ?string $startDate = null;

    public ?string $endDate = null;

    public bool $usesDateFilter = false;

    /** @var array<int, array<string, mixed>> */
    public array $teacherHours = [];

    public function mount(): void
    {
        $period = Period::get_default_period();
        $this->selectedPeriodId = $period?->id;

        if ($period) {
            $this->startDate = $period->start ? Carbon::parse($period->start)->toDateString() : now()->startOfMonth()->toDateString();
            $this->endDate = $period->end ? Carbon::parse($period->end)->toDateString() : now()->endOfMonth()->toDateString();
        }

        $this->loadData();
    }

    public function updatedSelectedPeriodId(): void
    {
        $period = Period::find($this->selectedPeriodId);

        if ($period) {
            $this->startDate = $period->start ? Carbon::parse($period->start)->toDateString() : null;
            $this->endDate = $period->end ? Carbon::parse($period->end)->toDateString() : null;
        }

        $this->usesDateFilter = false;
        $this->loadData();
    }

    public function updatedStartDate(): void
    {
        $this->usesDateFilter = true;
        $this->loadData();
    }

    public function updatedEndDate(): void
    {
        $this->usesDateFilter = true;
        $this->loadData();
    }

    public function clearDateFilter(): void
    {
        $period = Period::find($this->selectedPeriodId);

        if ($period) {
            $this->startDate = $period->start ? Carbon::parse($period->start)->toDateString() : null;
            $this->endDate = $period->end ? Carbon::parse($period->end)->toDateString() : null;
        }

        $this->usesDateFilter = false;
        $this->loadData();
    }

    protected function loadData(): void
    {
        if (! $this->startDate || ! $this->endDate) {
            return;
        }

        $period = Period::find($this->selectedPeriodId);

        $teachers = Teacher::query()
            ->with([
                'user',
                'events' => fn ($query) => $query
                    ->where('start', '>=', Carbon::parse($this->startDate)->startOfDay()->toDateTimeString())
                    ->where('end', '<=', Carbon::parse($this->endDate)->endOfDay()->toDateTimeString()),
                'leaves' => fn ($query) => $query
                    ->where('date', '>=', $this->startDate)
                    ->where('date', '<=', $this->endDate),
                'courses' => fn ($query) => $query
                    ->realcourses()
                    ->when(
                        $period,
                        fn ($courseQuery) => $courseQuery->where('period_id', $period->id),
                    )
                    ->whereDate('start_date', '<=', $this->endDate),
            ])
            ->get()
            ->sortBy(fn ($t) => $t->user?->name);

        $data = [];

        foreach ($teachers as $teacher) {
            // Theoretical volumes from course definitions (legacy "Heures prévues")
            $teacherCourses = $teacher->courses;
            $theoreticalFaceToFace = $period ? (float) $teacherCourses->sum('volume') : 0.0;
            $theoreticalRemote = $period ? (float) $teacherCourses->sum('remote_volume') : 0.0;

            // Actual hours from scheduled events (legacy "Heures sur le calendrier")
            $scheduledFaceToFace = (float) $teacher->events->sum('length');
            $scheduledRemote = $this->computeScheduledRemoteHours($teacherCourses, $this->startDate, $this->endDate);

            $leaveDays = $teacher->leaves->count();

            $data[] = [
                'teacherName' => $teacher->user?->name ?? __('Teacher').' #'.$teacher->id,
                'teacherId' => $teacher->id,
                'theoreticalFaceToFace' => round($theoreticalFaceToFace, 2),
                'theoreticalRemote' => round($theoreticalRemote, 2),
                'theoreticalTotal' => round($theoreticalFaceToFace + $theoreticalRemote, 2),
                'scheduledFaceToFace' => round($scheduledFaceToFace, 2),
                'scheduledRemote' => round($scheduledRemote, 2),
                'leaveDays' => $leaveDays,
            ];
        }

        $this->teacherHours = $data;
    }

    protected function computeScheduledRemoteHours(Collection $courses, string $startDate, string $endDate): float
    {
        $total = 0.0;

        foreach ($courses as $course) {
            $totalCourseWeeks = (int) ($course->start_date->diffInDays($course->end_date) / 7) + 1;
            $courseRemoteVolumePerWeek = $course->remote_volume / max(1, $totalCourseWeeks);

            $effectiveStartDate = Carbon::parse($course->start_date)->max($startDate);

            if ($effectiveStartDate <= $course->end_date) {
                $effectiveEndDate = Carbon::parse($course->end_date)->min($endDate);
                $numberOfWeeks = (int) ($effectiveStartDate->diffInDays($effectiveEndDate) / 7) + 1;

                $total += $courseRemoteVolumePerWeek * $numberOfWeeks;
            }
        }

        return $total;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Organization');
    }

    public static function getNavigationLabel(): string
    {
        return __('HR Dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return __('HR Dashboard');
    }
}
