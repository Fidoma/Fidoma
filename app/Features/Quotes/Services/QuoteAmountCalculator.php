<?php

namespace App\Features\Quotes\Services;

final class QuoteAmountCalculator
{
    /**
     * @param  array<int|string, array{quantity?: mixed, price?: mixed, tax_percent?: mixed, tax_included?: mixed, discount_amount?: mixed}>  $items
     * @param  array<int|string, array{tax_percent?: mixed, include_item_tax?: mixed}>  $quoteTaxes
     * @return array{items: array<int|string, array{subtotal: float, discount: float, tax: float, total: float}>, originalSubtotal: float, discount: float, subtotal: float, itemTax: float, quoteTax: float, total: float}
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
            $subtotal = $quantity * $price;

            if (! empty($item['tax_included']) && $taxPercent > 0) {
                $subtotal /= 1 + ($taxPercent / 100);
            }

            $subtotal = round($subtotal, 2);
            $originalSubtotal += $subtotal;
            $preparedItems[$key] = [
                'subtotal' => $subtotal,
                'tax_percent' => $taxPercent,
                'item_discount' => round($quantity * max(0, (float) ($item['discount_amount'] ?? 0)), 2),
            ];
        }

        $calculatedItems = [];
        $discountTotal = 0.0;
        $subtotal = 0.0;
        $itemTaxTotal = 0.0;

        foreach ($preparedItems as $key => $item) {
            $globalDiscount = 0.0;

            if ($discountPercent > 0) {
                $globalDiscount = round($item['subtotal'] * $discountPercent / 100, 2);
            } elseif (abs($discountAmount) > 0 && $originalSubtotal > 0) {
                $globalDiscount = round(abs($discountAmount) * ($item['subtotal'] / $originalSubtotal), 2);
            }

            $discount = min($item['subtotal'], $item['item_discount'] + $globalDiscount);
            $net = max(0, $item['subtotal'] - $discount);
            $tax = round($net * $item['tax_percent'] / 100, 2);
            $total = round($net + $tax, 2);

            $calculatedItems[$key] = [
                'subtotal' => $item['subtotal'],
                'discount' => round($discount, 2),
                'tax' => $tax,
                'total' => $total,
            ];
            $discountTotal += $discount;
            $subtotal += $net;
            $itemTaxTotal += $tax;
        }

        $subtotal = round($subtotal, 2);
        $itemTaxTotal = round($itemTaxTotal, 2);
        $quoteTaxTotal = 0.0;

        foreach ($quoteTaxes as $quoteTax) {
            $base = $subtotal + (! empty($quoteTax['include_item_tax']) ? $itemTaxTotal : 0);
            $quoteTaxTotal += $base * max(0, (float) ($quoteTax['tax_percent'] ?? 0)) / 100;
        }

        $quoteTaxTotal = round($quoteTaxTotal, 2);

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
            'total' => round($subtotal + $itemTaxTotal + $quoteTaxTotal, 2) * $sign,
        ];
    }
}
