<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\RelationManagers\InvoiceDetailsRelationManager;
use App\Filament\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 380;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('invoicing.accounting_enabled')
            && ! config('invoicing.price_categories_enabled');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Accounting');
    }

    public static function getModelLabel(): string
    {
        return __('Invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Invoices');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['invoiceType', 'payments', 'invoiceDetails']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date')
                    ->label(__('Date'))
                    ->required(),
                Select::make('invoice_type_id')
                    ->relationship('invoiceType', 'name')
                    ->label(__('Invoice Type'))
                    ->preload()
                    ->required(),
                TextInput::make('invoice_number')
                    ->label(__('Invoice Number'))
                    ->numeric()
                    ->visible(fn () => config('invoicing.invoice_numbering') !== 'manual'),
                TextInput::make('receipt_number')
                    ->label(__('Receipt Number'))
                    ->visible(fn () => config('invoicing.invoice_numbering') === 'manual'),
                TextInput::make('client_name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('client_idnumber')
                    ->label(__('ID Number'))
                    ->maxLength(255),
                TextInput::make('client_address')
                    ->label(__('Address'))
                    ->maxLength(255),
                TextInput::make('client_email')
                    ->label(__('Email'))
                    ->email()
                    ->maxLength(255),
                TextInput::make('client_phone')
                    ->label(__('Phone'))
                    ->maxLength(255),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $currencySymbol = config('academico.currency_symbol');
        $currencyPosition = config('academico.currency_position');
        $formatCurrency = fn ($value) => $currencyPosition === 'before'
            ? $currencySymbol.' '.number_format((float) $value, 2)
            : number_format((float) $value, 2).' '.$currencySymbol;

        return $infolist
            ->schema([
                Section::make(__('Invoice Summary'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('invoice_reference')
                            ->label(__('Invoice #')),
                        TextEntry::make('date')
                            ->label(__('Date'))
                            ->date(),
                        TextEntry::make('client_name')
                            ->label(__('Client')),
                        TextEntry::make('total_price_computed')
                            ->label(__('Total'))
                            ->state(fn (Invoice $record) => $formatCurrency($record->totalPrice())),
                        TextEntry::make('paid_total')
                            ->label(__('Total Received'))
                            ->state(fn (Invoice $record) => $formatCurrency($record->paidTotal())),
                        TextEntry::make('balance')
                            ->label(__('Balance'))
                            ->state(fn (Invoice $record) => $formatCurrency($record->balance))
                            ->color(fn (Invoice $record) => $record->balance > 0 ? 'danger' : 'success'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile: stacked invoice info
                TextColumn::make('mobile_invoice')
                    ->label(__('Invoice'))
                    ->state(fn ($record) => $record->invoice_reference.' · '.$record->invoiceType?->name)
                    ->description(fn ($record) => $record->client_name.' · '.$record->date?->format('M j, Y'))
                    ->searchable(query: fn ($query, $search) => $query->where('client_name', 'like', "%{$search}%")->orWhere('invoice_number', 'like', "%{$search}%"))
                    ->wrap()
                    ->hiddenFrom('md'),
                // Desktop columns
                TextColumn::make('invoice_reference')
                    ->label(__('Invoice #'))
                    ->searchable(['invoice_number', 'receipt_number'])
                    ->sortable('invoice_number')
                    ->visibleFrom('md'),
                TextColumn::make('invoiceType.name')
                    ->label(__('Type'))
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('date')
                    ->label(__('Date'))
                    ->date()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('client_name')
                    ->label(__('Client'))
                    ->wrap()
                    ->width('160px')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('client_idnumber')
                    ->label(__('ID Number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('lg'),
                TextColumn::make('client_email')
                    ->label(__('Email'))
                    ->wrap()
                    ->width('180px')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('lg'),
                TextColumn::make('total_price_with_currency')
                    ->label(__('Total'))
                    ->sortable(false),
                TextColumn::make('balance')
                    ->label(__('Balance'))
                    ->sortable(false)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(function ($state) {
                        $symbol = config('academico.currency_symbol');
                        $position = config('academico.currency_position');

                        return $position === 'before'
                            ? $symbol.' '.number_format($state, 2)
                            : number_format($state, 2).' '.$symbol;
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('from')->label(__('From')),
                        DatePicker::make('until')->label(__('Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),
                SelectFilter::make('invoice_type_id')
                    ->relationship('invoiceType', 'name')
                    ->label(__('Invoice Type'))
                    ->preload(),
            ])
            ->actions([
                Action::make('download_pdf')
                    ->label(__('PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Invoice $record) {
                        $service = app(InvoiceService::class);

                        return response()->streamDownload(function () use ($service, $record) {
                            echo $service->download($record)->stream()->getContent();
                        }, 'invoice-'.($record->invoice_reference ?? $record->id).'.pdf');
                    }),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoiceDetailsRelationManager::class,
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
