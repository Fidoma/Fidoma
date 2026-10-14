<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use LogicException;

final class RecordInvoicePayment
{
    public function __construct(private readonly RecalculateInvoice $recalculate) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Invoice $invoice, array $attributes): Payment
    {
        return DB::transaction(function () use ($invoice, $attributes): Payment {
            $invoice = Invoice::query()->lockForUpdate()->with('amount')->findOrFail($invoice->getKey());

            if ($invoice->invoice_type !== InvoiceType::Invoice) {
                throw new LogicException('Payments can only be recorded for regular invoices.');
            }

            if ($invoice->invoice_status_id === InvoiceStatus::Draft) {
                throw new LogicException('Payments cannot be recorded for draft invoices.');
            }

            $amount = round((float) ($attributes['payment_amount'] ?? 0), 2);
            $balance = round((float) ($invoice->amount?->invoice_balance ?? 0), 2);

            if ($amount <= 0 || $amount > $balance) {
                throw new LogicException('The payment amount must be greater than zero and may not exceed the open balance.');
            }

            $payment = $invoice->payments()->create([
                'payment_method_id' => $attributes['payment_method_id'],
                'payment_date' => $attributes['payment_date'],
                'payment_amount' => $amount,
                'payment_note' => $attributes['payment_note'] ?? '',
            ]);

            $this->recalculate->handle($invoice);

            return $payment;
        });
    }
}
