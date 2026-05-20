<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Periods\PeriodResource;
use App\Models\Period;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class PeriodInfo extends Widget
{
    protected static ?int $sort = -2;

    protected static string $view = 'filament.widgets.period-info';

    protected int|string|array $columnSpan = 'full';

    public function getData(): array
    {
        $periods = Cache::remember('widgets.period-info', 120, function (): array {
            return [
                'currentPeriod' => Period::get_default_period(),
                'enrollmentsPeriod' => Period::get_enrollments_period(),
            ];
        });

        return $periods;
    }

    public function getPeriodsUrl(): string
    {
        return PeriodResource::getUrl('index');
    }
}
