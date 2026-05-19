<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 382;

    public static function canAccess(): bool
    {
        return (auth()->user()?->hasRole('admin') ?? false)
            && (bool) config('invoicing.accounting_enabled');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Accounting');
    }

    public static function getModelLabel(): string
    {
        return __('Payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Payments');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['invoice.invoiceDetails', 'paymentmethod']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile: stacked payment info
                TextColumn::make('mobile_payment')
                    ->label(__('Payment'))
                    ->state(fn ($record) => $record->enrollment_name)
                    ->description(fn ($record) => collect([$record->month, $record->paymentmethod?->name])->filter()->implode(' · '))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('invoice', fn ($q) => $q->where('client_name', 'like', "%{$search}%")))
                    ->wrap()
                    ->hiddenFrom('md'),
                // Desktop columns
                TextColumn::make('month')
                    ->label(__('Month'))
                    ->sortable('date')
                    ->visibleFrom('md'),
                TextColumn::make('enrollment_name')
                    ->label(__('Student'))
                    ->wrap()
                    ->width('160px')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('invoice', fn ($q) => $q->where('client_name', 'like', "%{$search}%"));
                    })
                    ->visibleFrom('md'),
                TextColumn::make('paymentmethod.name')
                    ->label(__('Payment Method'))
                    ->visibleFrom('md'),
                TextColumn::make('value')
                    ->label(__('Amount'))
                    ->money(config('academico.currency_code', 'USD'))
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('Comment'))
                    ->wrap()
                    ->width('200px')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Filter::make('month')
                    ->form([
                        DatePicker::make('month')
                            ->label(__('Month'))
                            ->displayFormat('MM/YYYY'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['month'],
                            fn ($q, $date) => $q->whereMonth('date', date('m', strtotime($date)))
                                ->whereYear('date', date('Y', strtotime($date)))
                        );
                    }),
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
            'index' => ManagePayments::route('/'),
        ];
    }
}
