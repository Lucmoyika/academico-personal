<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Interfaces\InvoicingInterface;
use App\Services\InvoiceService;
use App\Traits\ReportsErrors;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewInvoice extends ViewRecord
{
    use ReportsErrors;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry_accounting')
                ->label(__('Retry send to accounting'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalDescription(__('This will attempt to send the invoice to the external accounting system.'))
                ->visible(fn () => (config('invoicing.accounting_enabled') || ! in_array(config('invoicing.invoicing_system'), ['internal', null], true)) && empty($this->getRecord()->receipt_number))
                ->action(function () {
                    try {
                        $invoicingSystem = config('invoicing.invoicing_system');
                        $serviceClass = config("invoicing.{$invoicingSystem}.class");

                        if (! $serviceClass) {
                            return;
                        }

                        $service = app($serviceClass);

                        if (! $service instanceof InvoicingInterface) {
                            return;
                        }

                        if (! $service->status()) {
                            Notification::make()
                                ->title(__('The accounting server is currently unavailable. Please try again later.'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $result = $service->saveInvoice($this->getRecord());

                        if ($result && $result !== 'ok') {
                            $this->getRecord()->update(['receipt_number' => $result]);
                        }

                        if ($result !== null) {
                            Notification::make()
                                ->title(__('Invoice sent to accounting successfully'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('The accounting server is currently unavailable. Please try again later.'))
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        $this->reportError($e, 'ViewInvoice::retryAccounting', [
                            'invoice_id' => $this->getRecord()->id,
                        ]);

                        Notification::make()
                            ->title(__('The accounting server is currently unavailable. Please try again later.'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('download_pdf')
                ->label(__('Download PDF'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(function () {
                    $service = app(InvoiceService::class);
                    $record = $this->getRecord();

                    return response()->streamDownload(function () use ($service, $record) {
                        echo $service->download($record)->stream()->getContent();
                    }, 'invoice-'.($record->invoice_reference ?? $record->id).'.pdf');
                }),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
