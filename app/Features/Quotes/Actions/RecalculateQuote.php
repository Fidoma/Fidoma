<?php

namespace App\Features\Quotes\Actions;

use App\Features\Quotes\Models\Quote;
use App\Features\Quotes\Models\QuoteTaxRate;
use App\Features\Quotes\Services\QuoteAmountCalculator;

final class RecalculateQuote
{
    public function __construct(
        private readonly QuoteAmountCalculator $calculator,
    ) {}

    public function handle(Quote $quote): Quote
    {
        $items = $quote->items()->with('taxRate')->get();
        $quoteTaxes = $quote->quoteTaxRates()->with('taxRate')->get();
        $amounts = $this->calculator->calculate(
            $items->mapWithKeys(fn ($item): array => [
                $item->getKey() => [
                    'quantity' => $item->item_quantity,
                    'price' => $item->item_price,
                    'tax_percent' => $item->taxRate?->tax_rate_percent,
                    'tax_included' => $item->item_tax_included,
                    'discount_amount' => $item->item_discount_amount,
                ],
            ])->all(),
            $quoteTaxes->map(fn (QuoteTaxRate $quoteTaxRate): array => [
                'tax_percent' => $quoteTaxRate->taxRate?->tax_rate_percent,
                'include_item_tax' => $quoteTaxRate->include_item_tax,
            ])->all(),
            (float) $quote->quote_discount_amount,
            (float) $quote->quote_discount_percent,
        );

        foreach ($items as $item) {
            $itemAmount = $amounts['items'][$item->getKey()];
            $item->amount()->updateOrCreate([], [
                'item_subtotal' => $itemAmount['subtotal'],
                'item_tax_total' => $itemAmount['tax'],
                'item_discount' => $itemAmount['discount'],
                'item_total' => $itemAmount['total'],
            ]);
        }

        $quoteTaxTotal = $amounts['quoteTax'];
        $quoteTaxBase = $amounts['subtotal'];
        $remainingQuoteTax = $quoteTaxTotal;

        foreach ($quoteTaxes as $index => $quoteTaxRate) {
            $amount = $index === $quoteTaxes->keys()->last()
                ? $remainingQuoteTax
                : round(
                    ($quoteTaxBase + ($quoteTaxRate->include_item_tax ? $amounts['itemTax'] : 0))
                    * (float) ($quoteTaxRate->taxRate?->tax_rate_percent ?? 0) / 100,
                    2,
                );
            $remainingQuoteTax -= $amount;
            $quoteTaxRate->updateQuietly(['quote_tax_rate_amount' => $amount]);
        }

        $quote->amount()->updateOrCreate([], [
            'quote_item_subtotal' => $amounts['subtotal'],
            'quote_item_tax_total' => $amounts['itemTax'],
            'quote_tax_total' => $amounts['quoteTax'],
            'quote_total' => $amounts['total'],
        ]);

        return $quote->refresh()->load('amount');
    }
}
