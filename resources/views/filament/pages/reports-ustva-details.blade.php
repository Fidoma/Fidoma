<div class="ustva-details">
    @if ($row && count($row['details']))
        <p class="ustva-details-description">{{ $row['description'] }}</p>
        @foreach ($row['details'] as $detail)
            <a class="ustva-detail-card" href="{{ \App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource::getUrl('edit', ['record' => $detail['invoice_id']]) }}">
                <div><strong>{{ $detail['invoice_number'] ?: 'Rechnung #'.$detail['invoice_id'] }}</strong><span>{{ $detail['client'] ?: 'Kein Kunde' }}</span></div>
                <dl>
                    <div><dt>Rechnungsdatum</dt><dd>{{ $detail['invoice_date'] ?: '–' }}</dd></div>
                    <div><dt>Steuerlicher Zeitraum</dt><dd>{{ $detail['tax_period'] ?: '–' }}</dd></div>
                    <div><dt>Netto</dt><dd>{{ number_format($detail['net'], 2, ',', '.') }} €</dd></div>
                    <div><dt>Umsatzsteuer</dt><dd>{{ number_format($detail['tax'], 2, ',', '.') }} €</dd></div>
                    <div><dt>Brutto</dt><dd>{{ number_format($detail['gross'], 2, ',', '.') }} €</dd></div>
                </dl>
            </a>
        @endforeach
    @else
        <x-filament::empty-state heading="Keine zugeordneten Datensätze" icon="heroicon-o-document-magnifying-glass" />
    @endif
</div>
