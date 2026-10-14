<?php

namespace App\Features\InvoiceGroups\Services;

use DateTimeInterface;

final class InvoiceGroupNumberFormatter
{
    public function format(
        string $format,
        int $nextId,
        int $leftPad = 0,
        ?DateTimeInterface $date = null,
    ): string {
        $date ??= now();

        return (string) preg_replace_callback(
            '/\{\{\{([^{|}]*)\}\}\}/',
            static fn (array $matches): string => match ($matches[1]) {
                'year' => $date->format('Y'),
                'yy' => $date->format('y'),
                'month' => $date->format('m'),
                'day' => $date->format('d'),
                'id' => str_pad((string) $nextId, max(0, $leftPad), '0', STR_PAD_LEFT),
                default => '',
            },
            $format,
        );
    }
}
