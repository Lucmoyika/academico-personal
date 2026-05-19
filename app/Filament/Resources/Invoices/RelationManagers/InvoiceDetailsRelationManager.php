<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoiceDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceDetails';

    public static function getTitle(mixed $ownerRecord, string $pageClass): string
    {
        return __('Products');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')
                    ->label(__('Product')),
                TextColumn::make('product_code')
                    ->label(__('Code'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('quantity')
                    ->label(__('Qty')),
                TextColumn::make('price')
                    ->label(__('Unit Price'))
                    ->money(config('academico.currency_code', 'USD')),
                TextColumn::make('comment')
                    ->label(__('Comment'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('product_name')
                ->label(__('Product'))
                ->required()
                ->maxLength(255),
            TextInput::make('product_code')
                ->label(__('Code'))
                ->maxLength(255),
            TextInput::make('quantity')
                ->label(__('Qty'))
                ->numeric()
                ->default(1)
                ->minValue(0.01)
                ->step(0.01)
                ->required(),
            TextInput::make('price')
                ->label(__('Unit Price'))
                ->numeric()
                ->required()
                ->step(0.01)
                ->prefix(config('academico.currency_position') === 'before' ? config('academico.currency_symbol') : null)
                ->suffix(config('academico.currency_position') === 'after' ? config('academico.currency_symbol') : null),
            TextInput::make('comment')
                ->label(__('Comment'))
                ->maxLength(255),
        ]);
    }
}
