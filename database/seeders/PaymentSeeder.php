<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Models\Paymentmethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $responsible = User::query()->where('email', 'academico@thomasdebay.com')->first();

        $defaultPaymentMethodCode = Paymentmethod::query()->value('code') ?? 'TC';

        Enrollment::query()->with(['student.user', 'course'])->get()->each(function (Enrollment $enrollment) use ($responsible, $defaultPaymentMethodCode): void {
            $studentUser = $enrollment->student?->user;
            $course = $enrollment->course;

            if (! $studentUser || ! $course) {
                return;
            }

            $invoice = Invoice::firstOrCreate(
                [
                    'client_email' => $studentUser->email,
                    'receipt_number' => sprintf('INV-%06d', $enrollment->id),
                ],
                [
                    'client_name' => $studentUser->name,
                    'client_idnumber' => $enrollment->student->idnumber,
                    'client_address' => $enrollment->student->address,
                    'client_phone' => $enrollment->student->phone->first()?->phone_number,
                    'company_id' => 1,
                    'invoice_type_id' => null,
                    'invoice_number' => null,
                    'date' => now()->toDateString(),
                ]
            );

            $coursePrice = (int) ($course->price ?? 0);

            InvoiceDetail::firstOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'product_type' => Enrollment::class,
                    'product_id' => $enrollment->id,
                ],
                [
                    'product_name' => $course->name,
                    'product_code' => 'COURSE-'.$course->id,
                    'price' => $coursePrice,
                    'tax_rate' => 0,
                    'quantity' => 1,
                    'final_price' => $coursePrice,
                    'comment' => 'Auto-generated from seeder',
                ]
            );

            Payment::firstOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'payment_method' => $defaultPaymentMethodCode,
                ],
                [
                    'responsable_id' => $responsible?->id ?? $studentUser->id,
                    'value' => (int) round($coursePrice * fake()->randomFloat(2, 0.4, 1)),
                    'comment' => 'Seed payment',
                ]
            );
        });
    }
}
