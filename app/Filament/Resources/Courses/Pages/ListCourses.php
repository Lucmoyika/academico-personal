<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('block_view')
                ->label(__('Switch to block view'))
                ->icon('heroicon-o-squares-2x2')
                ->url(CourseResource::getUrl('block-view')),
            CreateAction::make(),
        ];
    }
}
