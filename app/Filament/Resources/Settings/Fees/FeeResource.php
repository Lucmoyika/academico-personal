<?php

namespace App\Filament\Resources\Settings\Fees;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Settings\Fees\Pages\ManageFees;
use App\Models\Fee;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeeResource extends Resource
{
    protected static ?string $model = Fee::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    public static function getModelLabel(): string
    {
        return __('Fee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Fees');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->minLength(1)
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('price')
                    ->label(__('Price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix(config('academico.currency_position') === 'before' ? config('academico.currency_symbol') : null)
                    ->suffix(config('academico.currency_position') === 'after' ? config('academico.currency_symbol') : null),
                TextInput::make('product_code')
                    ->label(__('Product Code'))
                    ->nullable(),
                Checkbox::make('default')
                    ->label(__('Add automatically to every order')),
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
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->money(config('academico.currency_code', 'USD'))
                    ->sortable(),
                TextColumn::make('product_code')
                    ->label(__('Product Code')),
                IconColumn::make('default')
                    ->boolean()
                    ->label(__('Auto-add')),
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
            'index' => ManageFees::route('/'),
        ];
    }
}
