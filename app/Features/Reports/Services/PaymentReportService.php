<?php

namespace App\Features\Reports\Services;

use App\Features\Payments\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class PaymentReportService
{
    /** @return Builder<Payment> */
    public function query(
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $paymentMethodId = null,
    ): Builder {
        return Payment::query()
            ->with(['invoice.client', 'paymentMethod'])
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->when($paymentMethodId, fn (Builder $query, int $id): Builder => $query->where('payment_method_id', $id));
    }

    /** @return array{total: float, count: int, average: float} */
    public function summary(
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $paymentMethodId = null,
    ): array {
        $base = $this->query($start, $end, $paymentMethodId);
        $total = (float) (clone $base)->sum('payment_amount');
        $count = (clone $base)->count();

        return [
            'total' => round($total, 2),
            'count' => $count,
            'average' => $count > 0 ? round($total / $count, 2) : 0,
        ];
    }
}
