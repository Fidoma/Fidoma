<?php

namespace App\Features\Quotes\Services;

final class QuoteAmountCalculator
{
    /**
     * @param  array<int|string, array{quantity?: mixed, price?: mixed, tax_percent?: mixed, tax_included?: mixed, discount_amount?: mixed}>  $items
     * @param  array<int|string, array{tax_percent?: mixed, include_item_tax?: mixed}>  $quoteTaxes
     * @return array{items: array<int|string, array{subtotal: float, discount: float, tax: float, total: float}>, originalSubtotal: float, discount: float, subtotal: float, itemTax: float, quoteTax: float, taxBreakdown: array<int|string, float>, total: float}
     */
    public function calculate(
        array $items,
        array $quoteTaxes = [],
        float $discountAmount = 0,
        float $discountPercent = 0,
        int $sign = 1,
    ): array {
        $sign = $sign < 0 ? -1 : 1;
        $preparedItems = [];
        $originalSubtotal = 0.0;

        foreach ($items as $key => $item) {
            $quantity = max(0, $sign < 0 ? abs((float) ($item['quantity'] ?? 0)) : (float) ($item['quantity'] ?? 0));
            $price = max(0, (float) ($item['price'] ?? 0));
            $taxPercent = max(0, (float) ($item['tax_percent'] ?? 0));
            $gross = round($quantity * $price, 2);
            $subtotal = $gross;
            $taxIncluded = ! empty($item['tax_included']);

            if ($taxIncluded && $taxPercent > 0) {
                $subtotal /= 1 + ($taxPercent / 100);
            }

            $subtotal = round($subtotal, 2);
            $originalSubtotal += $subtotal;
            $preparedItems[$key] = [
                'subtotal' => $subtotal,
                'gross' => $gross,
                'tax_percent' => $taxPercent,
                'tax_included' => $taxIncluded,
                'item_discount' => round($quantity * max(0, (float) ($item['discount_amount'] ?? 0)), 2),
            ];
        }

        $calculatedItems = [];
        $discountTotal = 0.0;
        $subtotal = 0.0;
        $itemTaxTotal = 0.0;
        $taxBreakdown = [];

        foreach ($preparedItems as $key => $item) {
            $globalDiscount = 0.0;

            if ($discountPercent > 0) {
                $globalDiscount = round($item['subtotal'] * $discountPercent / 100, 2);
            } elseif (abs($discountAmount) > 0 && $originalSubtotal > 0) {
                $globalDiscount = round(abs($discountAmount) * ($item['subtotal'] / $originalSubtotal), 2);
            }

            $discount = min($item['subtotal'], $item['item_discount'] + $globalDiscount);
            $net = max(0, $item['subtotal'] - $discount);

            if ($item['tax_included']) {
                $grossDiscount = round($discount * (1 + ($item['tax_percent'] / 100)), 2);
                $total = max(0, round($item['gross'] - $grossDiscount, 2));
                $tax = round($total - $net, 2);
            } else {
                $tax = round($net * $item['tax_percent'] / 100, 2);
                $total = round($net + $tax, 2);
            }

            $calculatedItems[$key] = [
                'subtotal' => $item['subtotal'],
                'discount' => round($discount, 2),
                'tax' => $tax,
                'total' => $total,
            ];
            $discountTotal += $discount;
            $subtotal += $net;
            $itemTaxTotal += $tax;
            $taxRateKey = self::taxRateKey($item['tax_percent']);
            $taxBreakdown[$taxRateKey] = ($taxBreakdown[$taxRateKey] ?? 0) + $tax;
        }

        $subtotal = round($subtotal, 2);
        $itemTaxTotal = round($itemTaxTotal, 2);
        $quoteTaxTotal = 0.0;

        foreach ($quoteTaxes as $quoteTax) {
            $base = $subtotal + (! empty($quoteTax['include_item_tax']) ? $itemTaxTotal : 0);
            $taxPercent = max(0, (float) ($quoteTax['tax_percent'] ?? 0));
            $tax = $base * $taxPercent / 100;
            $quoteTaxTotal += $tax;
            $taxRateKey = self::taxRateKey($taxPercent);
            $taxBreakdown[$taxRateKey] = ($taxBreakdown[$taxRateKey] ?? 0) + $tax;
        }

        $quoteTaxTotal = round($quoteTaxTotal, 2);
        uksort($taxBreakdown, fn (int|string $left, int|string $right): int => (float) $left <=> (float) $right);
        $taxBreakdown = collect($taxBreakdown)
            ->map(fn (float $tax): float => round($tax, 2) * $sign)
            ->all();
        $taxDifference = round(
            (($itemTaxTotal + $quoteTaxTotal) * $sign) - array_sum($taxBreakdown),
            2,
        );

        if ($taxDifference !== 0.0 && $taxBreakdown !== []) {
            $lastTaxRate = array_key_last($taxBreakdown);
            $taxBreakdown[$lastTaxRate] = round($taxBreakdown[$lastTaxRate] + $taxDifference, 2);
        }

        return [
            'items' => collect($calculatedItems)->map(fn (array $item): array => [
                'subtotal' => $item['subtotal'] * $sign,
                'discount' => $item['discount'] * $sign,
                'tax' => $item['tax'] * $sign,
                'total' => $item['total'] * $sign,
            ])->all(),
            'originalSubtotal' => round($originalSubtotal, 2) * $sign,
            'discount' => round($discountTotal, 2) * $sign,
            'subtotal' => $subtotal * $sign,
            'itemTax' => $itemTaxTotal * $sign,
            'quoteTax' => $quoteTaxTotal * $sign,
            'taxBreakdown' => $taxBreakdown,
            'total' => round($subtotal + $itemTaxTotal + $quoteTaxTotal, 2) * $sign,
        ];
    }

    private static function taxRateKey(float $taxPercent): int|string
    {
        $key = rtrim(rtrim(number_format($taxPercent, 4, '.', ''), '0'), '.');

        return str_contains($key, '.') ? $key : (int) $key;
    }
}
