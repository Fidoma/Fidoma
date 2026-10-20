<?php

namespace App\Features\Terminals\Services;

use App\Features\Invoices\Actions\RecordInvoicePayment;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Terminals\Contracts\PaymentTerminalInterface;
use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Enums\TerminalConnectionStatus;
use App\Features\Terminals\Enums\TerminalOperatingStatus;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Models\PaymentTerminal;
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

    public function start(Invoice $invoice, PaymentTerminal $terminal, string $amount, User $user): TerminalPaymentAttempt
    {
        $attempt = DB::transaction(function () use ($invoice, $terminal, $amount, $user): TerminalPaymentAttempt {
            $terminal = PaymentTerminal::query()->where('user_id', $user->getKey())->lockForUpdate()->findOrFail($terminal->getKey());
            $invoice = Invoice::query()->where('user_id', $user->getKey())->lockForUpdate()->with('amount')->findOrFail($invoice->getKey());

            $this->validateStart($invoice, $terminal, $amount);

            $terminalBusy = TerminalPaymentAttempt::query()
                ->where('payment_terminal_id', $terminal->getKey())
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

            $terminal->update(['operating_status' => TerminalOperatingStatus::Busy]);

            $amountInCents = $this->toCents($amount);

            return TerminalPaymentAttempt::query()->create([
                'user_id' => $user->getKey(),
                'payment_terminal_id' => $terminal->getKey(),
                'invoice_id' => $invoice->getKey(),
                'amount' => intdiv($amountInCents, 100).'.'.str_pad((string) ($amountInCents % 100), 2, '0', STR_PAD_LEFT),
                'status' => TerminalPaymentStatus::Sending,
                'gateway_mode' => $this->gateway->mode(),
                'started_at' => now(),
            ]);
        });

        $attempt->load('terminal');

        return $this->applyGatewayResult($attempt, $this->gateway->start($attempt->terminal, $attempt));
    }

    public function refresh(TerminalPaymentAttempt $attempt, User $user): TerminalPaymentAttempt
    {
        $attempt = TerminalPaymentAttempt::query()
            ->where('user_id', $user->getKey())
            ->with('terminal')
            ->findOrFail($attempt->getKey());

        if ($attempt->status->isFinal()) {
            return $attempt;
        }

        return $this->applyGatewayResult($attempt, $this->gateway->status($attempt->terminal, $attempt));
    }

    private function applyGatewayResult(TerminalPaymentAttempt $attempt, GatewayPaymentResult $result): TerminalPaymentAttempt
    {
        return DB::transaction(function () use ($attempt, $result): TerminalPaymentAttempt {
            $attempt = TerminalPaymentAttempt::query()->lockForUpdate()->with(['terminal', 'invoice.amount'])->findOrFail($attempt->getKey());

            if ($attempt->payment_id !== null) {
                return $attempt;
            }

            $status = $result->status;

            if ($status === TerminalPaymentStatus::Successful && $attempt->gateway_mode !== 'simulator') {
                if ($attempt->terminal->payment_method_id === null) {
                    $status = TerminalPaymentStatus::Unknown;
                    $result = new GatewayPaymentResult($status, $result->reference, __('terminals::messages.errors.payment_method_missing'), $result->payload);
                } elseif ($this->toCents((string) $attempt->invoice->amount?->invoice_balance) < $this->toCents((string) $attempt->amount)) {
                    $status = TerminalPaymentStatus::Unknown;
                    $result = new GatewayPaymentResult($status, $result->reference, __('terminals::messages.errors.balance_changed'), $result->payload);
                } else {
                    $payment = $this->recordInvoicePayment->handle($attempt->invoice, [
                        'payment_method_id' => $attempt->terminal->payment_method_id,
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

            if ($status->isFinal()) {
                $attempt->terminal->update(['operating_status' => TerminalOperatingStatus::Idle]);
            }

            return $attempt->refresh();
        });
    }

    private function validateStart(Invoice $invoice, PaymentTerminal $terminal, string $amount): void
    {
        $balanceInCents = $this->toCents((string) ($invoice->amount?->invoice_balance ?? '0'));
        $amountInCents = $this->toCents($amount);

        if ($invoice->invoice_type !== InvoiceType::Invoice || $invoice->invoice_status_id !== InvoiceStatus::Open || $invoice->is_archived || $balanceInCents <= 0) {
            throw ValidationException::withMessages(['invoice' => __('terminals::messages.errors.invoice_not_payable')]);
        }

        if (! $terminal->is_enabled || $terminal->connection_status !== TerminalConnectionStatus::Online) {
            throw ValidationException::withMessages(['terminal' => __('terminals::messages.errors.terminal_unavailable')]);
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
