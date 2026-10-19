<?php

namespace App\Features\Payments\Actions;

use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeletePayment
{
    public function __construct(private readonly RecalculateInvoice $recalculate) {}

    public function handle(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->bankTransactionMappings()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Eine mit einer Banktransaktion verknüpfte Zahlung kann nicht gelöscht werden.',
                ]);
            }

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $payment->delete();
            $this->recalculate->handle($invoice);
        });
    }
}
