<?php

namespace App\Filament\Resources\Settings\ContactRelationships;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Settings\ContactRelationships\Pages\ManageContactRelationships;
use App\Models\ContactRelationship;
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

class ContactRelationshipResource extends Resource
{
    protected static ?string $model = ContactRelationship::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('People');
    }

    public static function getModelLabel(): string
    {
        return __('Contact Relationship');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Contact Relationships');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
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
            'index' => ManageContactRelationships::route('/'),
        ];
    }
}
