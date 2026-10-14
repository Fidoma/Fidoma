<?php

use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Models\Invoice;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $recalculate = app(RecalculateInvoice::class);
        Invoice::query()->orderBy('invoice_id')->each(fn (Invoice $invoice) => $recalculate->handle($invoice));
    }

    public function down(): void
    {
        // Calculated amount records remain valid when rolling back application code.
    }
};
