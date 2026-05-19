<?php

namespace App\Filament\Resources\Settings\Skills;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Settings\Skills\Pages\ManageSkills;
use App\Models\Skills\Skill;
use App\Models\Skills\SkillType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SkillResource extends Resource
{
    protected static ?string $model = Skill::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 7;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Academic');
    }

    public static function getModelLabel(): string
    {
        return __('Skill');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Skills');
    }

    public static function form(Form $form): Form
    {
        return $schema
            ->components([
                Select::make('skill_type_id')
                    ->label(__('Type'))
                    ->options(SkillType::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->minLength(1)
                    ->maxLength(1000)
                    ->unique(ignoreRecord: true),
                Select::make('level_id')
                    ->label(__('Level'))
                    ->relationship('level', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('skillType.name')
                    ->label(__('Type'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level.name')
                    ->label(__('Level'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('level_id')
                    ->label(__('Level'))
                    ->relationship('level', 'name'),
                SelectFilter::make('skill_type_id')
                    ->label(__('Type'))
                    ->options(SkillType::pluck('name', 'id')),
            ])
            ->actions([
                EditAction::make(),
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
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
            'index' => ManageSkills::route('/'),
        ];
    }
}
