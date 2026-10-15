<?php

namespace App\Features\Invoices\Services;

use App\Features\Invoices\Models\Invoice;

final class InvoiceTemplateData
{
    /** @return array<string, mixed> */
    public function sample(string $type = 'invoice'): array
    {
        $data = [
            'invoice' => ['number' => 'RE-2026-1042', 'date' => '15.10.2026', 'due_date' => '29.10.2026', 'terms' => 'Bitte überweisen Sie den Rechnungsbetrag innerhalb von 14 Tagen.'],
            'client' => ['number' => 'K-1048', 'company' => 'Musterwerk GmbH', 'name' => 'Anna Schneider', 'address' => 'Hafenstraße 18', 'city' => '20457 Hamburg', 'email' => 'anna.schneider@musterwerk.de'],
            'issuer' => ['company' => config('app.name', 'Fidoma').' GmbH', 'name' => 'Arianit Mustermann', 'address' => 'Beispielweg 12', 'city' => '10115 Berlin', 'email' => 'rechnung@fidoma.de', 'vat_id' => 'DE123456789', 'iban' => 'DE89 3704 0044 0532 0130 00'],
            'items' => [
                ['name' => 'Beratung und Konzeption', 'description' => 'Workshop und technische Konzeption', 'quantity' => 4, 'unit' => 'Std.', 'price' => 125, 'net_total' => 500, 'tax' => 19, 'total' => 595],
                ['name' => 'Implementierung', 'description' => 'Umsetzung der vereinbarten Funktionen', 'quantity' => 12, 'unit' => 'Std.', 'price' => 110, 'net_total' => 1320, 'tax' => 19, 'total' => 1570.80],
                ['name' => 'Hosting', 'description' => 'Hosting für Oktober 2026', 'quantity' => 1, 'unit' => 'Monat', 'price' => 49, 'net_total' => 49, 'tax' => 7, 'total' => 52.43],
            ],
            'tax_rows' => [
                ['name' => 'Umsatzsteuer', 'rate' => 7, 'amount' => 3.43],
                ['name' => 'Umsatzsteuer', 'rate' => 19, 'amount' => 345.80],
            ],
            'totals' => ['subtotal' => 1869, 'tax' => 349.23, 'total' => 2218.23],
        ];

        if ($type === 'quote') {
            $data['quote'] = [
                'number' => 'AN-2026-0187',
                'date' => '15.10.2026',
                'due_date' => '29.10.2026',
                'terms' => 'Dieses Angebot ist 14 Tage gültig.',
            ];
            unset($data['invoice']);
        }

        if ($type === 'reservation') {
            $data['reservation'] = [
                'number' => 'RES-2026-0412',
                'date' => '15.10.2026',
                'arrival_date' => '20.10.2026',
                'departure_date' => '24.10.2026',
                'terms' => 'Wir freuen uns auf Ihren Aufenthalt.',
            ];
            unset($data['invoice']);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function fromInvoice(Invoice $invoice): array
    {
        $invoice = app(InvoiceDocumentData::class)->prepare($invoice);
        $client = $invoice->client;
        $issuer = $invoice->user;

        return [
            'invoice' => [
                'number' => $invoice->invoice_number ?: 'Entwurf',
                'date' => $invoice->invoice_date_created?->format('d.m.Y') ?? '',
                'due_date' => $invoice->invoice_date_due?->format('d.m.Y') ?? '',
                'terms' => $invoice->invoice_terms ?? '',
            ],
            'client' => [
                'number' => (string) $invoice->client_id,
                'company' => $client?->client_company ?: $client?->client_name ?: '',
                'name' => $client?->client_company ? ($client?->client_name ?? '') : '',
                'address' => trim(($client?->client_address_1 ?? '').' '.($client?->client_address_2 ?? '')),
                'city' => trim(($client?->client_zip ?? '').' '.($client?->client_city ?? '')),
                'email' => $client?->client_email ?? '',
            ],
            'issuer' => [
                'company' => $issuer?->company_name ?: $issuer?->name ?: config('app.name', 'Fidoma'),
                'name' => $issuer?->name ?? '',
                'address' => trim(($issuer?->address_line_1 ?? '').' '.($issuer?->address_line_2 ?? '')),
                'city' => trim(($issuer?->postal_code ?? '').' '.($issuer?->city ?? '')),
                'email' => $issuer?->email ?? '',
                'vat_id' => $issuer?->vat_id ?? '',
                'iban' => $issuer?->iban ?? '',
            ],
            'items' => $invoice->items->map(fn ($item): array => [
                'name' => $item->item_name,
                'description' => $item->item_description ?? '',
                'quantity' => (float) $item->item_quantity,
                'unit' => $item->item_product_unit ?? '',
                'price' => (float) $item->item_price,
                'net_total' => (float) ($item->amount?->item_subtotal ?? 0),
                'tax' => (float) ($item->taxRate?->tax_rate_percent ?? 0),
                'total' => (float) ($item->amount?->item_total ?? 0),
            ])->all(),
            'tax_rows' => $this->taxRows($invoice),
            'totals' => [
                'subtotal' => (float) ($invoice->amount?->invoice_item_subtotal ?? 0),
                'tax' => (float) (($invoice->amount?->invoice_item_tax_total ?? 0) + ($invoice->amount?->invoice_tax_total ?? 0)),
                'total' => (float) ($invoice->amount?->invoice_total ?? 0),
            ],
        ];
    }

    /** @return array<int, array{name: string, rate: float, amount: float}> */
    private function taxRows(Invoice $invoice): array
    {
        $rows = [];

        foreach ($invoice->items as $item) {
            $rate = (float) ($item->taxRate?->tax_rate_percent ?? 0);
            $key = $this->taxRateKey($rate);
            $rows[$key] ??= [
                'name' => $item->taxRate?->tax_rate_name ?: 'Umsatzsteuer',
                'rate' => $rate,
                'amount' => 0.0,
            ];
            $rows[$key]['amount'] += (float) ($item->amount?->item_tax_total ?? 0);
        }

        foreach ($invoice->invoiceTaxRates as $invoiceTaxRate) {
            $rate = (float) ($invoiceTaxRate->taxRate?->tax_rate_percent ?? 0);
            $key = $this->taxRateKey($rate);
            $rows[$key] ??= [
                'name' => $invoiceTaxRate->taxRate?->tax_rate_name ?: 'Umsatzsteuer',
                'rate' => $rate,
                'amount' => 0.0,
            ];
            $rows[$key]['amount'] += (float) $invoiceTaxRate->invoice_tax_rate_amount;
        }

        uasort($rows, fn (array $left, array $right): int => $left['rate'] <=> $right['rate']);

        return array_values(array_map(fn (array $row): array => [
            ...$row,
            'amount' => round($row['amount'], 2),
        ], $rows));
    }

    private function taxRateKey(float $rate): string
    {
        return number_format($rate, 4, '.', '');
    }
}
