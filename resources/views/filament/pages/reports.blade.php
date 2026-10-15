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

        @if ($activeTab === 'tax' && $taxView === 'ustva')
            <div class="reports-period-filter">
                <div class="reports-filter-field">
                    <label for="ustva-frequency">Zeitraum</label>
                    <x-filament::input.wrapper><x-filament::input.select id="ustva-frequency" wire:model.live="ustvaFrequency">
                        <option value="month">Monat</option><option value="quarter">Quartal</option>
                    </x-filament::input.select></x-filament::input.wrapper>
                </div>
                <div class="reports-filter-field">
                    <label for="ustva-year">Jahr</label>
                    <x-filament::input.wrapper><x-filament::input.select id="ustva-year" wire:model.live="ustvaYear">
                        @foreach ($this->ustvaYears() as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach
                    </x-filament::input.select></x-filament::input.wrapper>
                </div>
                @if ($ustvaFrequency === 'month')
                    <div class="reports-filter-field">
                        <label for="ustva-month">Monat</label>
                        <x-filament::input.wrapper><x-filament::input.select id="ustva-month" wire:model.live="ustvaMonth">
                            @foreach (range(1, 12) as $month)<option value="{{ $month }}">{{ \Carbon\CarbonImmutable::create(2026, $month, 1)->translatedFormat('F') }}</option>@endforeach
                        </x-filament::input.select></x-filament::input.wrapper>
                    </div>
                @else
                    <div class="reports-filter-field">
                        <label for="ustva-quarter">Quartal</label>
                        <x-filament::input.wrapper><x-filament::input.select id="ustva-quarter" wire:model.live="ustvaQuarter">
                            @foreach (range(1, 4) as $quarter)<option value="{{ $quarter }}">Q{{ $quarter }}</option>@endforeach
                        </x-filament::input.select></x-filament::input.wrapper>
                    </div>
                @endif
                <div class="reports-filter-field">
                    <label for="taxation-type">Versteuerung</label>
                    <x-filament::input.wrapper><x-filament::input.select id="taxation-type" wire:model.live="taxationType">
                        <option value="soll">Sollversteuerung</option><option value="ist">Istversteuerung</option>
                    </x-filament::input.select></x-filament::input.wrapper>
                </div>
            </div>
        @else
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
        @endif
    </div>

    @if ($activeTab === 'tax')
        <x-filament::tabs label="Umsatzsteueransicht" class="reports-tax-tabs">
            <x-filament::tabs.item wire:click="setTaxView('overview')" :active="$taxView === 'overview'">Übersicht</x-filament::tabs.item>
            <x-filament::tabs.item wire:click="setTaxView('ustva')" :active="$taxView === 'ustva'">UStVA-Vorschau</x-filament::tabs.item>
        </x-filament::tabs>
    @endif

    @php($summary = $activeTab === 'tax' && $taxView === 'ustva' ? null : $this->summary())

    @if (! ($activeTab === 'tax' && $taxView === 'ustva'))
    <div class="reports-kpis">
        @if ($activeTab === 'tax')
            <x-filament::section compact><span>Gesamter Nettoumsatz</span><strong>{{ number_format($summary['net'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Gesamte Umsatzsteuer</span><strong>{{ number_format($summary['tax'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Abziehbare Vorsteuer</span><strong class="reports-muted">Nicht verfügbar</strong></x-filament::section>
            <x-filament::section compact><span>Umsatzsteuerdifferenz</span><strong class="reports-muted">Nicht berechenbar</strong></x-filament::section>
        @elseif ($activeTab === 'payments')
            <x-filament::section compact><span>Zahlungseingänge</span><strong class="reports-positive">{{ number_format($summary['incoming'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Zahlungsausgänge</span><strong class="reports-negative">{{ number_format($summary['outgoing'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Saldo</span><strong>{{ number_format($summary['balance'], 2, ',', '.') }} €</strong></x-filament::section>
        @else
            <x-filament::section compact><span>Anzahl Rechnungen</span><strong>{{ $summary['count'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Bezahlt</span><strong class="reports-positive">{{ $summary['paid'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Offen</span><strong>{{ $summary['open'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Teilbezahlt</span><strong class="reports-warning">{{ $summary['partial'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Gesamtbetrag</span><strong>{{ number_format($summary['total'], 2, ',', '.') }} €</strong></x-filament::section>
        @endif
    </div>
    @endif

    <x-filament::section>
        <div class="reports-table-header">
            <div class="reports-secondary-filters">
                @if ($activeTab === 'payments')
                    <div class="reports-filter-field">
                        <label for="payment-direction">Richtung</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="payment-direction" wire:model.live="paymentDirection">
                                <option value="all">Alle</option>
                                <option value="incoming">Zahlungseingänge</option>
                                <option value="outgoing">Zahlungsausgänge</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
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
                @if ($activeTab === 'tax' && $taxView === 'ustva')
                    <x-filament::button color="gray" size="sm" icon="heroicon-o-document-arrow-down" wire:click="exportUstvaPdf">PDF</x-filament::button>
                @endif
                <x-filament::button color="gray" size="sm" icon="heroicon-o-document-text" wire:click="exportCsv">CSV</x-filament::button>
                <x-filament::button color="gray" size="sm" icon="heroicon-o-table-cells" wire:click="exportExcel">Excel</x-filament::button>
            </div>
        </div>

        @if ($activeTab === 'tax' && $taxView === 'ustva')
            @include('filament.pages.reports-ustva', ['report' => $this->ustvaReport()])
        @else
            {{ $this->table }}
        @endif

        @if ($activeTab === 'tax' && $taxView === 'overview')
            <div class="reports-tax-total">
                <strong>Gesamt</strong>
                <span>{{ number_format($summary['net'], 2, ',', '.') }} € netto</span>
                <span>{{ number_format($summary['tax'], 2, ',', '.') }} € Umsatzsteuer</span>
                <span>{{ number_format($summary['gross'], 2, ',', '.') }} € brutto</span>
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
