<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Period;
use App\Services\StatService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $period = Period::get_default_period();

        if (! $period) {
            return [];
        }

        $numbers = Cache::remember(
            'widgets.stats-overview.'.$period->id,
            120,
            function () use ($period): array {
                $stats = new StatService(external: false, partner: null, reference: $period);

                return [
                    'enrollments' => $stats->enrollmentsCount(),
                    'paid' => $stats->paidEnrollmentsCount(),
                    'pending' => $stats->pendingEnrollmentsCount(),
                    'students' => $stats->studentsCount(),
                    'new_students' => $stats->newStudentsCount(),
                ];
            }
        );

        return [
            Stat::make(__('Enrollments'), $numbers['enrollments'])
                ->description($period->name)
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make(__('Paid Enrollments'), $numbers['paid'])
                ->description(__('Pending').': '.$numbers['pending'])
                ->icon('heroicon-o-credit-card')
                ->color('success'),

            Stat::make(__('Students'), $numbers['students'])
                ->description($period->name)
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make(__('New Students'), $numbers['new_students'])
                ->description($period->name)
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->url(StudentResource::getUrl('index', ['filters' => ['new_in_period' => ['period_id' => $period->id]]])),
        ];
    }
}
