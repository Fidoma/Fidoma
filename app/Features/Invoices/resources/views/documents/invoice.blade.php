<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_type->label() }} {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        .preview-bar { margin: 0 auto 24px; max-width: 900px; padding: 12px 16px; background: #172033; text-align: right; }
        .preview-bar a { display: inline-block; padding: 8px 14px; border-radius: 5px; background: #fff; color: #172033; font-weight: bold; text-decoration: none; }
        .document { margin: 0 auto; max-width: 900px; }
        .header, .addresses, .summary { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand { font-size: 22px; font-weight: bold; color: #111827; }
        .document-title { text-align: right; }
        .document-title h1 { margin: 0 0 5px; font-size: 23px; color: #111827; }
        .muted { color: #667085; }
        .addresses { margin-top: 38px; }
        .addresses td { width: 50%; vertical-align: top; }
        .addresses .recipient { padding-right: 30px; }
        .label { margin-bottom: 6px; color: #667085; font-size: 8px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
        .recipient-name { font-size: 13px; font-weight: bold; }
        .meta { margin-left: auto; width: 100%; max-width: 290px; border-collapse: collapse; }
        .meta td { padding: 3px 0 3px 12px; }
        .meta td:first-child { padding-left: 0; color: #667085; }
        .meta td:last-child { text-align: right; font-weight: bold; }
        .original-reference { margin-top: 8px; text-align: right; }
        .items { margin-top: 38px; width: 100%; border-collapse: collapse; }
        .items th { padding: 8px 7px; border-bottom: 2px solid #172033; color: #475467; font-size: 8px; letter-spacing: .05em; text-align: left; text-transform: uppercase; }
        .items td { padding: 10px 7px; border-bottom: 1px solid #d8dee9; vertical-align: top; }
        .items .number { text-align: right; white-space: nowrap; }
        .item-name { font-weight: bold; }
        .item-description { margin-top: 3px; color: #667085; font-size: 9px; white-space: pre-line; }
        .summary-wrap { margin-top: 22px; margin-left: auto; width: 48%; }
        .summary td { padding: 5px 0 5px 12px; }
        .summary td:first-child { padding-left: 0; color: #475467; }
        .summary td:last-child { text-align: right; white-space: nowrap; }
        .summary .total td { padding-top: 9px; border-top: 2px solid #172033; color: #111827; font-size: 13px; font-weight: bold; }
        .terms { margin-top: 34px; padding-top: 13px; border-top: 1px solid #d8dee9; page-break-inside: avoid; }
        .footer { margin-top: 34px; color: #667085; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
@if ($preview)
    <div class="preview-bar">
        <a href="{{ route('invoices.pdf', ['invoice' => $invoice]) }}">PDF öffnen</a>
    </div>
@endif

@php
    $money = static fn ($value): string => number_format((float) $value, 2, ',', '.').' €';
    $quantity = static fn ($value): string => number_format((float) $value, 2, ',', '.');
    $client = $invoice->client;
    $issuer = $invoice->user;
@endphp

<main class="document">
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ $issuer?->company_name ?: config('app.name', 'Fidoma') }}</div>
                @if ($issuer)
                    <div class="muted">{{ $issuer->name }}</div>
                    @if ($issuer->address_line_1)<div class="muted">{{ $issuer->address_line_1 }}</div>@endif
                    @if ($issuer->address_line_2)<div class="muted">{{ $issuer->address_line_2 }}</div>@endif
                    @if ($issuer->postal_code || $issuer->city)
                        <div class="muted">{{ trim($issuer->postal_code.' '.$issuer->city) }}</div>
                    @endif
                    @if ($issuer->country)<div class="muted">{{ $issuer->country }}</div>@endif
                    <div class="muted">{{ $issuer->email }}</div>
                    @if ($issuer->phone)<div class="muted">{{ $issuer->phone }}</div>@endif
                @endif
            </td>
            <td class="document-title">
                <h1>{{ $invoice->invoice_type->label() }}</h1>
                <div>{{ $invoice->invoice_number ?: 'Entwurf' }}</div>
            </td>
        </tr>
    </table>

    <table class="addresses">
        <tr>
            <td class="recipient">
                <div class="label">Rechnungsempfänger</div>
                <div class="recipient-name">{{ $client?->client_company ?: $client?->client_name }}</div>
                @if ($client?->client_company && $client?->client_name)
                    <div>{{ $client->client_name }}</div>
                @endif
                @if ($client?->client_address_1)<div>{{ $client->client_address_1 }}</div>@endif
                @if ($client?->client_address_2)<div>{{ $client->client_address_2 }}</div>@endif
                <div>{{ trim(($client?->client_zip ?? '').' '.($client?->client_city ?? '')) }}</div>
                @if ($client?->client_country)<div>{{ $client->client_country }}</div>@endif
                @if ($client?->client_email)<div>{{ $client->client_email }}</div>@endif
            </td>
            <td>
                <table class="meta">
                    <tr><td>Belegnummer</td><td>{{ $invoice->invoice_number ?: 'Entwurf' }}</td></tr>
                    <tr><td>Belegdatum</td><td>{{ $invoice->invoice_date_created?->format('d.m.Y') }}</td></tr>
                    <tr><td>Fällig am</td><td>{{ $invoice->invoice_date_due?->format('d.m.Y') }}</td></tr>
                    <tr><td>Kundennummer</td><td>{{ $invoice->client_id }}</td></tr>
                </table>
                @if ($invoice->parentInvoice)
                    <div class="original-reference">
                        Bezug: {{ $invoice->parentInvoice->invoice_number }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%">Pos.</th>
                <th style="width: 43%">Leistung / Produkt</th>
                <th class="number" style="width: 10%">Menge</th>
                <th class="number" style="width: 14%">Einzelpreis</th>
                <th class="number" style="width: 10%">Steuer</th>
                <th class="number" style="width: 18%">Gesamt</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($invoice->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <div class="item-name">{{ $item->item_name }}</div>
                    @if ($item->item_description)
                        <div class="item-description">{{ $item->item_description }}</div>
                    @endif
                </td>
                <td class="number">
                    {{ $quantity($item->item_quantity) }}
                    @if ($item->item_product_unit) {{ $item->item_product_unit }} @endif
                </td>
                <td class="number">{{ $money($item->item_price) }}</td>
                <td class="number">{{ $quantity($item->taxRate?->tax_rate_percent ?? 0) }} %</td>
                <td class="number">{{ $money($item->amount?->item_total ?? 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Keine Positionen vorhanden.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary">
            <tr><td>Zwischensumme</td><td>{{ $money($invoice->amount?->invoice_item_subtotal ?? 0) }}</td></tr>
            <tr><td>Steuer Positionen</td><td>{{ $money($invoice->amount?->invoice_item_tax_total ?? 0) }}</td></tr>
            @foreach ($invoice->invoiceTaxRates as $invoiceTaxRate)
                <tr>
                    <td>{{ $invoiceTaxRate->taxRate?->tax_rate_name ?? 'Rechnungssteuer' }}</td>
                    <td>{{ $money($invoiceTaxRate->invoice_tax_rate_amount) }}</td>
                </tr>
            @endforeach
            @if ((float) $invoice->invoice_discount_amount !== 0.0)
                <tr><td>Rabatt</td><td>- {{ $money($invoice->invoice_discount_amount) }}</td></tr>
            @endif
            <tr class="total"><td>Gesamt</td><td>{{ $money($invoice->amount?->invoice_total ?? 0) }}</td></tr>
        </table>
    </div>

    @if ($invoice->invoice_terms)
        <section class="terms">
            <div class="label">Hinweise und Zahlungsbedingungen</div>
            <div>{!! nl2br(e($invoice->invoice_terms)) !!}</div>
        </section>
    @endif

    <footer class="footer">
        @if ($issuer)
            {{ $issuer->company_name ?: $issuer->name }}
            @if ($issuer->vat_id) · USt-IdNr. {{ $issuer->vat_id }} @endif
            @if ($issuer->tax_number) · Steuernummer {{ $issuer->tax_number }} @endif
            @if ($issuer->iban) · IBAN {{ $issuer->iban }} @endif
            @if ($issuer->bic) · BIC {{ $issuer->bic }} @endif
        @else
            Erstellt mit {{ config('app.name', 'Fidoma') }}
        @endif
    </footer>
</main>
</body>
</html>
