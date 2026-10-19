<?php

namespace App\Features\Invoices\Services;

final class InvoiceLayoutDefaults
{
    public function html(string $type = 'invoice'): string
    {
        $html = <<<'HTML'
<main class="document">
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ issuer.company }}</div>
                <div class="muted">{{ issuer.name }}</div>
                <div class="muted">{{ issuer.address }}</div>
                <div class="muted">{{ issuer.city }}</div>
                <div class="muted">{{ issuer.email }}</div>
            </td>
            <td class="document-title">
                <h1>{{ invoice.title }}</h1>
                <div>{{ invoice.number }}</div>
            </td>
        </tr>
    </table>

    <table class="addresses">
        <tr>
            <td class="recipient">
                <div class="label">Rechnungsempfänger</div>
                <div class="recipient-name">{{ client.company }}</div>
                <div>{{ client.name }}</div>
                <div>{{ client.address }}</div>
                <div>{{ client.city }}</div>
                <div>{{ client.email }}</div>
            </td>
            <td>
                <table class="meta">
                    <tr><td>Rechnungsnummer</td><td>{{ invoice.number }}</td></tr>
                    <tr><td>Rechnungsdatum</td><td>{{ invoice.date }}</td></tr>
                    <tr><td>Fällig am</td><td>{{ invoice.due_date }}</td></tr>
                    <tr><td>Kundennummer</td><td>{{ client.number }}</td></tr>
                    <tr><td>Projekt</td><td>{{ project.number }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%">Pos.</th>
                <th style="width: 33%">Leistung / Produkt</th>
                <th class="number" style="width: 10%">Menge</th>
                <th class="number" style="width: 12%">Einzelpreis</th>
                <th class="number" style="width: 14%">Gesamt netto</th>
                <th class="number" style="width: 10%">Steuer</th>
                <th class="number" style="width: 16%">Gesamt</th>
            </tr>
        </thead>
        <tbody>{{ items }}</tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary">
            <tr><td>Zwischensumme</td><td>{{ totals.subtotal }}</td></tr>
            {{ tax_rows }}
            <tr><td>Steuer gesamt</td><td>{{ totals.tax }}</td></tr>
            <tr class="total"><td>Gesamt</td><td>{{ totals.total }}</td></tr>
        </table>
    </div>

    <section class="terms">
        <div class="label">Hinweise und Zahlungsbedingungen</div>
        <div>{{ invoice.terms }}</div>
    </section>

    <footer class="footer">
        {{ issuer.company }} · USt-IdNr. {{ issuer.vat_id }} · IBAN {{ issuer.iban }}
    </footer>
</main>
HTML;

        return match ($type) {
            'quote' => str_replace(
                ['Rechnungsempfänger', '<h1>{{ invoice.title }}</h1>', 'Rechnungsnummer', 'Rechnungsdatum', 'Fällig am', '{{ invoice.', '<tr><td>Projekt</td><td>{{ project.number }}</td></tr>'],
                ['Angebotsempfänger', '<h1>Angebot</h1>', 'Angebotsnummer', 'Angebotsdatum', 'Gültig bis', '{{ quote.', ''],
                $html,
            ),
            'reservation' => str_replace(
                ['Rechnungsempfänger', '<h1>{{ invoice.title }}</h1>', 'Rechnungsnummer', 'Rechnungsdatum', 'Fällig am', '{{ invoice.number }}', '{{ invoice.date }}', '{{ invoice.due_date }}', '{{ invoice.terms }}', '<tr><td>Projekt</td><td>{{ project.number }}</td></tr>'],
                ['Reservierung für', '<h1>Reservierung</h1>', 'Reservierungsnummer', 'Reservierungsdatum', 'Abreise', '{{ reservation.number }}', '{{ reservation.date }}', '{{ reservation.departure_date }}', '{{ reservation.terms }}', ''],
                $html,
            ),
            default => $html,
        };
    }

    public function css(): string
    {
        return <<<'CSS'
@page { margin: 18mm 16mm; }
* { box-sizing: border-box; }
body { margin: 0; color: #172033; background: #fff; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
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
CSS;
    }
}
