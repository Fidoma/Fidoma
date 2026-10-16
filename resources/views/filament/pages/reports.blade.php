<x-filament-panels::page>
    <div class="reports-toolbar">
        <x-filament::tabs label="Berichtsart">
            <x-filament::tabs.item wire:click="setTab('tax')" :active="$activeTab === 'tax'" icon="heroicon-o-receipt-percent">
                Umsatzsteuer
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="setTab('payments')" :active="$activeTab === 'payments'" icon="heroicon-o-banknotes">
                Zahlungen
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="setTab('invoices')" :active="$activeTab === 'invoices'" icon="heroicon-o-document-currency-euro">
                Rechnungen
            </x-filament::tabs.item>
        </x-filament::tabs>

        <form wire:submit="applyPeriod" class="reports-period-filter">
            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-o-x-circle"
                aria-label="Datumsfilter leeren"
                tooltip="Datumsfilter zurücksetzen"
                wire:click="clearDateFilter"
            />
            <div class="reports-filter-field">
                <label for="reports-start">Von</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="reports-start" type="date" wire:model="startDate" />
                </x-filament::input.wrapper>
            </div>
            <div class="reports-filter-field">
                <label for="reports-end">Bis</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="reports-end" type="date" wire:model="endDate" />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">Auswerten</x-filament::button>
        </form>
    </div>

    @if ($activeTab === 'tax')
        <div class="reports-taxation-tabs">
            <x-filament::tabs label="Versteuerungsart">
                <x-filament::tabs.item wire:click="setTaxationType('soll')" :active="$taxationType === 'soll'">Soll-Versteuerung</x-filament::tabs.item>
                <x-filament::tabs.item wire:click="setTaxationType('ist')" :active="$taxationType === 'ist'">Ist-Versteuerung</x-filament::tabs.item>
            </x-filament::tabs>
        </div>
    @endif

    @php($ustvaReport = $activeTab === 'tax' ? $this->ustvaReport() : null)
    @php($summary = $activeTab === 'tax' ? null : $this->summary())

    <div class="reports-kpis">
        @if ($activeTab === 'tax')
            <x-filament::section compact><span>Gesamter Nettoumsatz</span><strong>{{ number_format($ustvaReport['net'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Gesamte Umsatzsteuer</span><strong>{{ number_format($ustvaReport['output_tax'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Abziehbare Vorsteuer</span><strong class="reports-muted">Nicht verfügbar</strong></x-filament::section>
            <x-filament::section compact><span>Umsatzsteuerdifferenz</span><strong class="reports-muted">Nicht berechenbar</strong></x-filament::section>
        @elseif ($activeTab === 'payments')
            <x-filament::section compact><span>Gesamtzahlungen</span><strong class="reports-positive">{{ number_format($summary['total'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Anzahl Zahlungen</span><strong>{{ $summary['count'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Durchschnittlicher Zahlbetrag</span><strong>{{ number_format($summary['average'], 2, ',', '.') }} €</strong></x-filament::section>
        @else
            <x-filament::section compact><span>Anzahl Rechnungen</span><strong>{{ $summary['count'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Bezahlt</span><strong class="reports-positive">{{ $summary['paid'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Offen</span><strong>{{ $summary['open'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Teilbezahlt</span><strong class="reports-warning">{{ $summary['partial'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Gesamtbetrag</span><strong>{{ number_format($summary['total'], 2, ',', '.') }} €</strong></x-filament::section>
        @endif
    </div>

    <x-filament::section>
        <div class="reports-table-header">
            <div class="reports-secondary-filters">
                @if ($activeTab === 'payments')
                    <div class="reports-filter-field">
                        <label for="payment-method">Zahlungsart</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="payment-method" wire:model.live="paymentMethodId">
                                <option value="">Alle Zahlungsarten</option>
                                @foreach ($this->paymentMethods() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @elseif ($activeTab === 'invoices')
                    <div class="reports-filter-field">
                        <label for="invoice-status">Rechnungsstatus</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="invoice-status" wire:model.live="invoiceStatus">
                                <option value="">Alle Status</option>
                                @foreach (\App\Features\Invoices\Enums\InvoiceStatus::options() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <div class="reports-filter-field">
                        <label for="invoice-client">Kunde</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="invoice-client" wire:model.live="clientId">
                                <option value="">Alle Kunden</option>
                                @foreach ($this->clients() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </div>

            <div class="reports-export-actions">
                @if ($activeTab === 'tax')
                    <x-filament::button color="gray" size="sm" icon="heroicon-o-document-arrow-down" wire:click="exportUstvaPdf">PDF</x-filament::button>
                @endif
                <x-filament::button color="gray" size="sm" icon="heroicon-o-document-text" wire:click="exportCsv">CSV</x-filament::button>
                <x-filament::button color="gray" size="sm" icon="heroicon-o-table-cells" wire:click="exportExcel">Excel</x-filament::button>
            </div>
        </div>

        @if ($activeTab === 'tax')
            @include('filament.pages.reports-ustva', ['report' => $ustvaReport])
        @else
            {{ $this->table }}
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
