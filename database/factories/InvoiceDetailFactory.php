<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceDetail>
 */
class InvoiceDetailFactory extends Factory
{
    protected $model = InvoiceDetail::class;

    public function definition(): array
    {
        $price = fake()->numberBetween(5000, 30000);

        return [
            'invoice_id' => Invoice::factory(),
            'product_name' => fake()->words(2, true),
            'product_code' => strtoupper(fake()->bothify('PRD-###')),
            'product_id' => fake()->numberBetween(1, 9999),
            'product_type' => 'manual',
            'price' => $price,
            'tax_rate' => 0,
            'quantity' => 1,
            'final_price' => $price,
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
