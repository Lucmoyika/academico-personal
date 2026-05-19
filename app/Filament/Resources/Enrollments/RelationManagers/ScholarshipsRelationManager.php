<?php

namespace App\Filament\Resources\Enrollments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScholarshipsRelationManager extends RelationManager
{
    protected static string $relationship = 'scholarships';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(mixed $ownerRecord, string $pageClass): string
    {
        return __('Scholarships');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name')),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                DetachAction::make(),
            ]);
    }
}
