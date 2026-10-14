<?php

namespace Tests\Unit;

use App\Features\InvoiceGroups\Services\InvoiceGroupNumberFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceGroupNumberFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_all_supported_invoiceplane_placeholders(): void
    {
        $formatter = new InvoiceGroupNumberFormatter;

        $number = $formatter->format(
            'ANG-{{{year}}}{{{month}}}{{{day}}}-{{{yy}}}-{{{id}}}',
            23,
            5,
            new DateTimeImmutable('2026-10-13'),
        );

        $this->assertSame('ANG-20261013-26-00023', $number);
    }

    #[Test]
    public function it_removes_unknown_placeholders_like_invoiceplane(): void
    {
        $formatter = new InvoiceGroupNumberFormatter;

        $number = $formatter->format('RE-{{{unknown}}}-{{{id}}}', 7);

        $this->assertSame('RE--7', $number);
    }
}
