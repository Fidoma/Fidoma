<?php

namespace App\Features\Reports\Services;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\PaymentMethods\Models\PaymentMethod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class PaymentReportService
{
    /** @return Builder<BankTransaction> */
    public function query(
        CarbonInterface $start,
        CarbonInterface $end,
        string $direction = 'all',
        ?int $paymentMethodId = null,
        ?string $bankAccount = null,
    ): Builder {
        $paymentMethodName = $paymentMethodId
            ? PaymentMethod::query()->whereKey($paymentMethodId)->value('payment_method_name')
            : null;

        return BankTransaction::query()
            ->with(['mappings.payment.invoice.client', 'mappings.payment.paymentMethod'])
            ->where('pending', false)
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->when($direction === 'incoming', fn (Builder $query): Builder => $query->where('amount', '>', 0))
            ->when($direction === 'outgoing', fn (Builder $query): Builder => $query->where('amount', '<', 0))
            ->when($bankAccount, fn (Builder $query, string $iban): Builder => $query->where('account_iban', $iban))
            ->when($paymentMethodId, fn (Builder $query, int $id): Builder => $query->where(function (Builder $filter) use ($id, $paymentMethodName): void {
                $filter->whereHas(
                    'mappings.payment',
                    fn (Builder $paymentQuery): Builder => $paymentQuery->where('payment_method_id', $id),
                );

                if (filled($paymentMethodName)) {
                    $filter->orWhere(function (Builder $fallback) use ($paymentMethodName): void {
                        $fallback
                            ->whereDoesntHave('mappings.payment.paymentMethod')
                            ->where('booking_text', $paymentMethodName);
                    });
                }
            }));
    }

    /** @return array{incoming: float, outgoing: float, balance: float} */
    public function summary(
        CarbonInterface $start,
        CarbonInterface $end,
        string $direction = 'all',
        ?int $paymentMethodId = null,
        ?string $bankAccount = null,
    ): array {
        $base = $this->query($start, $end, $direction, $paymentMethodId, $bankAccount);
        $incoming = (float) (clone $base)->where('amount', '>', 0)->sum('amount');
        $outgoing = abs((float) (clone $base)->where('amount', '<', 0)->sum('amount'));

        return [
            'incoming' => round($incoming, 2),
            'outgoing' => round($outgoing, 2),
            'balance' => round($incoming - $outgoing, 2),
        ];
    }

    public function paymentType(BankTransaction $transaction): string
    {
        return $transaction->mappings
            ->first(fn ($mapping) => $mapping->payment?->paymentMethod)
            ?->payment?->paymentMethod?->payment_method_name
            ?: ($transaction->booking_text ?: 'Banktransaktion');
    }

    public function invoiceNumber(BankTransaction $transaction): ?string
    {
        return $transaction->mappings
            ->first(fn ($mapping) => $mapping->payment?->invoice)
            ?->payment?->invoice?->invoice_number;
    }
}
