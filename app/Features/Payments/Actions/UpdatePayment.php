<?php

namespace App\Features\Payments\Actions;

use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdatePayment
{
    public function __construct(private readonly RecalculateInvoice $recalculate) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Payment $payment, array $attributes): Payment
    {
        return DB::transaction(function () use ($payment, $attributes): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $invoice = Invoice::query()->with('amount')->lockForUpdate()->findOrFail($payment->invoice_id);
            $amount = round((float) ($attributes['payment_amount'] ?? 0), 2);
            $otherPayments = round((float) $invoice->payments()
                ->whereKeyNot($payment->getKey())
                ->sum('payment_amount'), 2);
            $maximumAmount = max(0, round((float) ($invoice->amount?->invoice_total ?? 0) - $otherPayments, 2));

            if ($amount <= 0 || $amount > $maximumAmount) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'Der Zahlbetrag muss größer als null sein und darf den verbleibenden Rechnungsbetrag nicht überschreiten.',
                ]);
            }

            $payment->update([
                'payment_method_id' => $attributes['payment_method_id'],
                'payment_date' => $attributes['payment_date'],
                'payment_amount' => $amount,
                'payment_note' => $attributes['payment_note'] ?? '',
            ]);

            $this->recalculate->handle($invoice);

            return $payment->refresh();
        });
    }
}
