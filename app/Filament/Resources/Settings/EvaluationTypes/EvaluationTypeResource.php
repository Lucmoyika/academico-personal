<?php

namespace App\Filament\Resources\Settings\EvaluationTypes;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Settings\EvaluationTypes\Pages\ManageEvaluationTypes;
use App\Models\EvaluationType;
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
use Filament\Tables\Table;

class EvaluationTypeResource extends Resource
{
    protected static ?string $model = EvaluationType::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Academic');
    }

    public static function getModelLabel(): string
    {
        return __('Evaluation Type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Evaluation Types');
    }

    public static function form(Form $form): Form
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->minLength(1)
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('gradeTypes')
                    ->label(__('Grade Types'))
                    ->relationship('gradeTypes', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('skills')
                    ->label(__('Skills'))
                    ->relationship('skills', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
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
            ])
            ->filters([
                //
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
            'index' => ManageEvaluationTypes::route('/'),
        ];
    }
}
