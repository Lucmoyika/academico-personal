<?php

namespace App\Filament\Resources\AttendanceTypes\Pages;

use App\Filament\Resources\AttendanceTypes\AttendanceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAttendanceTypes extends ManageRecords
{
    protected static string $resource = AttendanceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
