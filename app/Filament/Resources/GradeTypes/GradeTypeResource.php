<?php

namespace App\Filament\Resources\GradeTypes;

use App\Filament\Resources\GradeTypes\Pages\ManageGradeTypes;
use App\Models\GradeType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GradeTypeResource extends Resource
{
    protected static ?string $model = GradeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $cluster = \App\Filament\Clusters\Settings\SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Academic');
    }

    public static function getModelLabel(): string
    {
        return __('Grade Type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Grade Types');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->minLength(1)
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('total')
                    ->required()
                    ->integer()
                    ->minValue(0),
                Select::make('grade_type_category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name')
                    ->label(__('Category'))
                    ->searchable()
                    ->preload(),
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
                TextColumn::make('total')
                    ->label(__('Total'))
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGradeTypes::route('/'),
        ];
    }
}
