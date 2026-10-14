<?php

namespace App\Features\Reservations\Actions;

use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\ReservationTaxRate;

final class RecalculateReservation
{
    public function __construct(private readonly QuoteAmountCalculator $calculator) {}

    public function handle(Reservation $reservation): Reservation
    {
        $items = $reservation->items()->with('taxRate')->get();
        $taxRates = $reservation->reservationTaxRates()->with('taxRate')->get();
        $amounts = $this->calculator->calculate(
            $items->mapWithKeys(fn ($item): array => [$item->getKey() => [
                'quantity' => $item->item_quantity,
                'price' => $item->item_price,
                'tax_percent' => $item->taxRate?->tax_rate_percent,
                'tax_included' => $item->item_tax_included,
                'discount_amount' => $item->item_discount_amount,
            ]])->all(),
            $taxRates->map(fn (ReservationTaxRate $rate): array => [
                'tax_percent' => $rate->taxRate?->tax_rate_percent,
                'include_item_tax' => $rate->include_item_tax,
            ])->all(),
            (float) $reservation->reservation_discount_amount,
            (float) $reservation->reservation_discount_percent,
        );

        foreach ($items as $item) {
            $itemAmount = $amounts['items'][$item->getKey()];
            $item->amount()->updateOrCreate([], [
                'item_subtotal' => $itemAmount['subtotal'], 'item_tax_total' => $itemAmount['tax'],
                'item_discount' => $itemAmount['discount'], 'item_total' => $itemAmount['total'],
            ]);
        }

        foreach ($taxRates as $rate) {
            $base = $amounts['subtotal'] + ($rate->include_item_tax ? $amounts['itemTax'] : 0);
            $rate->updateQuietly([
                'reservation_tax_rate_amount' => round($base * (float) ($rate->taxRate?->tax_rate_percent ?? 0) / 100, 2),
            ]);
        }

        $reservation->amount()->updateOrCreate([], [
            'reservation_item_subtotal' => $amounts['subtotal'],
            'reservation_item_tax_total' => $amounts['itemTax'],
            'reservation_tax_total' => $amounts['quoteTax'],
            'reservation_total' => $amounts['total'],
        ]);

        return $reservation->refresh()->load('amount');
    }
}
