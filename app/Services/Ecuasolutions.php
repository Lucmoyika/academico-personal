<?php

namespace App\Services;

use App\Interfaces\InvoicingInterface;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Traits\ReportsErrors;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ecuasolutions implements InvoicingInterface
{
    use ReportsErrors;

    public function status(): bool
    {
        $pingUrl = config('invoicing.ecuasolutions.ping_url');

        if (empty($pingUrl)) {
            return false;
        }

        try {
            Http::timeout(3)->get($pingUrl);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function saveInvoice(Invoice $invoice): ?string
    {
        $url = config('invoicing.ecuasolutions.url');
        $key = config('invoicing.ecuasolutions.key');

        if (empty($url) || empty($key)) {
            Log::warning('Ecuasolutions: URL or key not configured, skipping invoice send.');

            return null;
        }

        $ivkardex = [];
        $pckardex = [];
        $notes = [];

        foreach ($invoice->payments as $p => $payment) {
            $pckardex[$p] = [
                'codforma' => $payment->payment_method,
                'valor' => $payment->value,
                'fechaemision' => $invoice->created_at,
                'fechavenci' => $invoice->created_at,
                'observacion' => $payment->comment,
                'codprovcli' => $invoice->client_idnumber,
            ];
        }

        foreach ($invoice->invoiceDetails as $product) {
            $ivkardex[] = [
                'codinventario' => $product->product_code,
                'codbodega' => 'MAT',
                'cantidad' => 1,
                'descuento' => 0,
                'iva' => 0,
                'preciototal' => $product->final_price,
                'valoriva' => 0,
            ];

            if ($product->product instanceof Enrollment) {
                if ($product->product->student_name) {
                    $notes[] = 'Taller de Francés de '.$product->product->student_name;
                }
                if ($product->product->course?->level?->name) {
                    $notes[] = 'Nivel: '.$product->product->course?->level?->name;
                }
                if ($product->product->course?->period?->name) {
                    $notes[] = 'Ciclo: '.$product->product->course?->period?->name;
                }
            }
        }

        $body = [
            'codtrans' => 'FE',
            'numtrans' => $invoice->id,
            'fechatrans' => $invoice->created_at,
            'horatrans' => $invoice->created_at,
            'descripcion' => implode(' - ', $notes),
            'codusuario' => 'web',
            'codprovcli' => $invoice->client_idnumber,
            'nombre' => $invoice->client_name,
            'direccion' => $invoice->client_address,
            'telefono' => $invoice->client_phone,
            'email' => $invoice->client_email,
            'codvendedor' => '',
            'ivkardex' => $ivkardex,
            'pckardex' => $pckardex,
        ];

        Log::info('Sending data to accounting');
        Log::info('request sent: '.json_encode($body));

        $code = null;

        try {
            $response = Http::retry(2, 1000)
                ->timeout(12)
                ->withHeaders([
                    'authorization' => $key,
                ])
                ->post($url, $body);

            if ($response->body()) {
                $code = json_decode(
                    preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $response->body()),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            }

            Log::info('response: '.$response->body());

            return $code['mensaje'] ?? null;
        } catch (\Throwable $e) {
            $this->reportError($e, 'Ecuasolutions::saveInvoice', [
                'invoice_id' => $invoice->id,
            ]);

            return null;
        }
    }
}
