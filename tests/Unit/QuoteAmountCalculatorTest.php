<?php

namespace Tests\Unit;

use App\Features\Quotes\Services\QuoteAmountCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QuoteAmountCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_discounts_item_tax_and_quote_tax(): void
    {
        $calculator = new QuoteAmountCalculator;

        $amounts = $calculator->calculate(
            [
                ['quantity' => 2, 'price' => 50, 'tax_percent' => 19, 'discount_amount' => 5],
                ['quantity' => 1, 'price' => 100, 'tax_percent' => 7],
            ],
            [['tax_percent' => 2, 'include_item_tax' => true]],
            discountPercent: 10,
        );

        $this->assertSame(200.0, $amounts['originalSubtotal']);
        $this->assertSame(30.0, $amounts['discount']);
        $this->assertSame(170.0, $amounts['subtotal']);
        $this->assertSame(21.5, $amounts['itemTax']);
        $this->assertSame(3.83, $amounts['quoteTax']);
        $this->assertSame([2 => 3.83, 7 => 6.3, 19 => 15.2], $amounts['taxBreakdown']);
        $this->assertSame(195.33, $amounts['total']);
    }

    #[Test]
    public function it_extracts_included_tax_from_the_entered_price(): void
    {
        $calculator = new QuoteAmountCalculator;

        $amounts = $calculator->calculate([
            ['quantity' => 1, 'price' => 119, 'tax_percent' => 19, 'tax_included' => true],
        ]);

        $this->assertSame(100.0, $amounts['subtotal']);
        $this->assertSame(19.0, $amounts['itemTax']);
        $this->assertSame([19 => 19.0], $amounts['taxBreakdown']);
        $this->assertSame(119.0, $amounts['total']);
    }

    #[Test]
    public function it_groups_included_item_tax_by_rate_and_keeps_zero_percent_visible(): void
    {
        $calculator = new QuoteAmountCalculator;

        $amounts = $calculator->calculate([
            ['quantity' => 1, 'price' => 107, 'tax_percent' => 7, 'tax_included' => true],
            ['quantity' => 1, 'price' => 119, 'tax_percent' => 19, 'tax_included' => true],
            ['quantity' => 1, 'price' => 50, 'tax_percent' => 0, 'tax_included' => true],
        ]);

        $this->assertSame([0 => 0.0, 7 => 7.0, 19 => 19.0], $amounts['taxBreakdown']);
        $this->assertSame(26.0, array_sum($amounts['taxBreakdown']));
        $this->assertSame(276.0, $amounts['total']);
    }

    #[Test]
    public function it_calculates_a_counterpart_with_negative_amounts(): void
    {
        $calculator = new QuoteAmountCalculator;

        $amounts = $calculator->calculate(
            [['quantity' => -2, 'price' => 50, 'tax_percent' => 19, 'discount_amount' => 5]],
            discountPercent: 10,
            sign: -1,
        );

        $this->assertSame(-100.0, $amounts['originalSubtotal']);
        $this->assertSame(-20.0, $amounts['discount']);
        $this->assertSame(-80.0, $amounts['subtotal']);
        $this->assertSame(-15.2, $amounts['itemTax']);
        $this->assertSame(-95.2, $amounts['total']);
        $this->assertSame(-95.2, $amounts['items'][0]['total']);
    }
}
