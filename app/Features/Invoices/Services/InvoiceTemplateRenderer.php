<?php

namespace App\Features\Invoices\Services;

use Illuminate\Support\Arr;

final class InvoiceTemplateRenderer
{
    /** @param array<string, mixed> $data */
    public function render(string $html, string $css, array $data): string
    {
        $html = $this->removeExecutableMarkup($html);
        $css = str_ireplace(['</style', '<?', '?>'], ['<\/style', '', ''], $css);
        $items = $this->renderItems($data['items'] ?? []);
        $taxRowsData = $data['tax_rows'] ?? [];
        $taxRowsData = is_array($taxRowsData) ? $taxRowsData : iterator_to_array($taxRowsData);

        if ($this->hasSingleCompleteTaxRow($taxRowsData, $data['totals']['tax'] ?? 0)) {
            $html = $this->removeTotalTaxRow($html);
        }

        $taxRows = $this->renderTaxRows($taxRowsData);

        $html = str_replace('{{ items }}', $items, $html);
        $html = str_replace('{{ tax_rows }}', $taxRows, $html);
        $html = preg_replace_callback('/\{\{\s*([a-z_]+(?:\.[a-z_]+)*)\s*\}\}/i', function (array $match) use ($data): string {
            $value = Arr::get($data, $match[1], '');

            if (str_starts_with($match[1], 'totals.')) {
                return $this->money($value);
            }

            return e((string) $value);
        }, $html) ?? $html;

        return '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>'.$css.'</style></head><body>'.$html.'</body></html>';
    }

    private function removeExecutableMarkup(string $html): string
    {
        $html = preg_replace('/<\?(?:php|=)?.*?\?>/is', '', $html) ?? $html;
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        return $html;
    }

    /** @param iterable<int, array<string, mixed>> $items */
    private function renderItems(iterable $items): string
    {
        $rows = [];

        foreach ($items as $index => $item) {
            $description = $item['description'] ? '<div class="item-description">'.nl2br(e((string) $item['description'])).'</div>' : '';
            $rows[] = '<tr><td>'.($index + 1).'</td><td><div class="item-name">'.e((string) $item['name']).'</div>'.$description.'</td><td class="number">'.$this->number($item['quantity']).' '.e((string) $item['unit']).'</td><td class="number">'.$this->money($item['price']).'</td><td class="number">'.$this->money($item['net_total']).'</td><td class="number">'.$this->number($item['tax']).' %</td><td class="number">'.$this->money($item['total']).'</td></tr>';
        }

        return $rows !== [] ? implode('', $rows) : '<tr><td colspan="7">Keine Positionen vorhanden.</td></tr>';
    }

    /** @param iterable<int, array{name: string, rate: float, amount: float}> $taxRows */
    private function renderTaxRows(iterable $taxRows): string
    {
        $rows = [];

        foreach ($taxRows as $taxRow) {
            $rate = rtrim(rtrim(number_format($taxRow['rate'], 2, ',', ''), '0'), ',');
            $rows[] = sprintf(
                '<tr><td>%s</td><td>%s</td></tr>',
                e($this->taxLabel($taxRow['name'], $rate)),
                $this->money($taxRow['amount']),
            );
        }

        return implode('', $rows);
    }

    /** @param array<int, array{name: string, rate: float, amount: float}> $taxRows */
    private function hasSingleCompleteTaxRow(array $taxRows, mixed $totalTax): bool
    {
        return count($taxRows) === 1
            && round((float) $taxRows[0]['amount'], 2) === round((float) $totalTax, 2);
    }

    private function removeTotalTaxRow(string $html): string
    {
        return preg_replace(
            '/<tr\b[^>]*>(?:(?!<\/tr>)[\s\S])*?\{\{\s*totals\.tax\s*\}\}(?:(?!<\/tr>)[\s\S])*?<\/tr>\s*/i',
            '',
            $html,
            1,
        ) ?? $html;
    }

    private function taxLabel(string $name, string $rate): string
    {
        $normalizedName = str_replace([',', ' '], ['.', ''], trim($name));
        $normalizedRate = str_replace(',', '.', $rate);

        if (preg_match('/(^|\D)'.preg_quote($normalizedRate, '/').'(?:\.0+)?%($|\D)/', $normalizedName) === 1) {
            return 'Steuer '.$rate.' %';
        }

        return trim($name).' '.$rate.' %';
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.').' €';
    }

    private function number(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }
}
