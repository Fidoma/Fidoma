<?php

namespace App\Features\Terminals\Services;

use App\Features\Invoices\Actions\RecordInvoicePayment;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Terminals\Contracts\PaymentTerminalInterface;
use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TerminalPaymentService
{
    public function __construct(
        private readonly PaymentTerminalInterface $gateway,
        private readonly RecordInvoicePayment $recordInvoicePayment,
    ) {}

    public function start(Invoice $invoice, string $amount, User $user): TerminalPaymentAttempt
    {
        $attempt = DB::transaction(function () use ($invoice, $amount, $user): TerminalPaymentAttempt {
            $invoice = Invoice::query()->where('user_id', $user->getKey())->lockForUpdate()->with('amount')->findOrFail($invoice->getKey());

            $this->validateStart($invoice, $amount);

            $terminalBusy = TerminalPaymentAttempt::query()
                ->whereIn('status', $this->activeStatuses())
                ->exists();
            $invoiceBusy = TerminalPaymentAttempt::query()
                ->where('invoice_id', $invoice->getKey())
                ->whereIn('status', $this->activeStatuses())
                ->exists();

            if ($terminalBusy || $invoiceBusy) {
                throw ValidationException::withMessages([
                    'payment' => __('terminals::messages.errors.already_active'),
                ]);
            }

            $amountInCents = $this->toCents($amount);

            return TerminalPaymentAttempt::query()->create([
                'user_id' => $user->getKey(),
                'invoice_id' => $invoice->getKey(),
                'amount' => intdiv($amountInCents, 100).'.'.str_pad((string) ($amountInCents % 100), 2, '0', STR_PAD_LEFT),
                'status' => TerminalPaymentStatus::Sending,
                'gateway_mode' => 'zvtapi',
                'started_at' => now(),
            ]);
        });

        return $this->applyGatewayResult($attempt, $this->gateway->start($attempt));
    }

    public function refresh(TerminalPaymentAttempt $attempt, User $user): TerminalPaymentAttempt
    {
        $attempt = TerminalPaymentAttempt::query()
            ->where('user_id', $user->getKey())
            ->findOrFail($attempt->getKey());

        if ($attempt->status->isFinal()) {
            return $attempt;
        }

        return $this->applyGatewayResult($attempt, $this->gateway->status($attempt));
    }

    private function applyGatewayResult(TerminalPaymentAttempt $attempt, GatewayPaymentResult $result): TerminalPaymentAttempt
    {
        return DB::transaction(function () use ($attempt, $result): TerminalPaymentAttempt {
            $attempt = TerminalPaymentAttempt::query()->lockForUpdate()->with('invoice.amount')->findOrFail($attempt->getKey());

            if ($attempt->payment_id !== null) {
                return $attempt;
            }

            $status = $result->status;

            if (filled($result->reference) && TerminalPaymentAttempt::query()
                ->where('gateway_reference', $result->reference)
                ->whereKeyNot($attempt->getKey())
                ->exists()) {
                $status = TerminalPaymentStatus::Unknown;
                $result = new GatewayPaymentResult(
                    $status,
                    $attempt->gateway_reference,
                    __('terminals::messages.errors.duplicate_gateway_reference'),
                    $result->payload,
                    $result->simulated,
                );
            }

            if ($status === TerminalPaymentStatus::Successful) {
                $paymentMethodId = (int) config('terminal.payment_method_id');

                if ($paymentMethodId <= 0 || ! PaymentMethod::query()->whereKey($paymentMethodId)->exists()) {
                    $status = TerminalPaymentStatus::Unknown;
                    $result = new GatewayPaymentResult($status, $result->reference, __('terminals::messages.errors.payment_method_missing'), $result->payload);
                } elseif ($this->toCents((string) $attempt->invoice->amount?->invoice_balance) < $this->toCents((string) $attempt->amount)) {
                    $status = TerminalPaymentStatus::Unknown;
                    $result = new GatewayPaymentResult($status, $result->reference, __('terminals::messages.errors.balance_changed'), $result->payload);
                } else {
                    $payment = $this->recordInvoicePayment->handle($attempt->invoice, [
                        'payment_method_id' => $paymentMethodId,
                        'payment_date' => now()->toDateString(),
                        'payment_amount' => $attempt->amount,
                        'payment_note' => __('terminals::messages.payment_note', ['id' => $attempt->getKey(), 'reference' => $result->reference ?: '–']),
                    ]);
                    $attempt->payment_id = $payment->getKey();
                }
            }

            $attempt->fill([
                'status' => $status,
                'gateway_reference' => $result->reference ?: $attempt->gateway_reference,
                'status_message' => $result->message,
                'gateway_payload' => $result->payload,
                'completed_at' => $status->isFinal() ? now() : null,
            ])->save();

            return $attempt->refresh();
        });
    }

    private function validateStart(Invoice $invoice, string $amount): void
    {
        $balanceInCents = $this->toCents((string) ($invoice->amount?->invoice_balance ?? '0'));
        $amountInCents = $this->toCents($amount);

        if ($invoice->invoice_type !== InvoiceType::Invoice || $invoice->invoice_status_id !== InvoiceStatus::Open || $invoice->is_archived || $balanceInCents <= 0) {
            throw ValidationException::withMessages(['invoice' => __('terminals::messages.errors.invoice_not_payable')]);
        }

        if ($amountInCents <= 0 || $amountInCents > $balanceInCents) {
            throw ValidationException::withMessages(['amount' => __('terminals::messages.errors.invalid_amount')]);
        }
    }

    private function toCents(string $amount): int
    {
        $normalized = str_replace(',', '.', trim($amount));

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $normalized, $matches)) {
            return 0;
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    /** @return list<string> */
    private function activeStatuses(): array
    {
        return array_map(
            fn (TerminalPaymentStatus $status): string => $status->value,
            array_filter(TerminalPaymentStatus::cases(), fn (TerminalPaymentStatus $status): bool => $status->isActive() || $status === TerminalPaymentStatus::Unknown),
        );
    }
}
