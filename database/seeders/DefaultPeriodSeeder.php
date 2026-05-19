<?php

namespace Database\Seeders;

use App\Models\Config;
use App\Models\Period;
use App\Models\Year;
use Illuminate\Database\Seeder;

class DefaultPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $year = Year::firstOrCreate([
            'name' => now()->year,
        ]);

        $period = Period::updateOrCreate(
            ['name' => 'Default'],
            [
                'start' => now()->subDays(15)->toDateString(),
                'end' => now()->addDays(120)->toDateString(),
                'year_id' => $year->id,
            ]
        );

        Config::updateOrCreate(
            ['name' => 'current_period'],
            ['value' => $period->id]
        );

        Config::updateOrCreate(
            ['name' => 'default_enrollment_period'],
            ['value' => $period->id]
        );
    }
}
