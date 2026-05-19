<?php

namespace App\Filament\RelationManagers;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(mixed $ownerRecord, string $pageClass): string
    {
        return __('Comments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author.name')
                    ->label(__('Author')),
                TextColumn::make('body')
                    ->label(__('Comment'))
                    ->wrap()
                    ->limit(100),
                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Add a comment'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['author_id'] = auth()->id();

                        return $data;
                    }),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('body')
                ->label(__('Comment'))
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Hidden::make('author_id')
                ->default(auth()->id()),
        ]);
    }
}
