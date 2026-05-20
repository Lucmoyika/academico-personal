<?php

namespace App\Filament\Resources\Settings\EnrollmentStatusTypes;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Settings\EnrollmentStatusTypes\Pages\ManageEnrollmentStatusTypes;
use App\Models\EnrollmentStatusType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentStatusTypeResource extends Resource
{
    protected static ?string $model = EnrollmentStatusType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Academic');
    }

    public static function getModelLabel(): string
    {
        return __('Enrollment Status Type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Enrollment Status Types');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                ColorPicker::make('color')
                    ->label(__('Color'))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('color')
                    ->label(__('Color')),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEnrollmentStatusTypes::route('/'),
        ];
    }
}
