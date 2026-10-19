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
            <x-filament::tabs.item wire:click="setTab('sales')" :active="$activeTab === 'sales'" icon="heroicon-o-shopping-cart">
                Verkauf
            </x-filament::tabs.item>
        </x-filament::tabs>

        <form wire:submit="applyPeriod" class="reports-period-filter">
            <x-filament::dropdown placement="bottom-start">
                <x-slot name="trigger">
                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-calendar-days"
                        icon-position="before"
                    >
                        {{ $this->periodLabel() }}
                    </x-filament::button>
                </x-slot>

                <x-filament::dropdown.list>
                    @foreach (['q1' => 'Q1', 'q2' => 'Q2', 'q3' => 'Q3', 'q4' => 'Q4', 'year' => 'Jahr', 'reset' => 'Gesamt'] as $period => $label)
                        <x-filament::dropdown.list.item
                            :color="$this->selectedPeriod === $period ? 'primary' : 'gray'"
                            :icon="$this->selectedPeriod === $period ? 'heroicon-m-check' : null"
                            role="button"
                            x-on:click="close"
                            wire:click="selectPeriod('{{ $period }}')"
                        >
                            {{ $label }}
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>

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
        @elseif ($activeTab === 'invoices')
            <x-filament::section compact><span>Anzahl Rechnungen</span><strong>{{ $summary['count'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Bezahlt</span><strong class="reports-positive">{{ $summary['paid'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Offen</span><strong>{{ $summary['open'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Teilbezahlt</span><strong class="reports-warning">{{ $summary['partial'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Gesamtbetrag</span><strong>{{ number_format($summary['total'], 2, ',', '.') }} €</strong></x-filament::section>
        @else
            <x-filament::section compact><span>Anzahl Positionen</span><strong>{{ $summary['count'] }}</strong></x-filament::section>
            <x-filament::section compact><span>Netto</span><strong>{{ number_format($summary['net'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Umsatzsteuer</span><strong>{{ number_format($summary['tax'], 2, ',', '.') }} €</strong></x-filament::section>
            <x-filament::section compact><span>Brutto</span><strong>{{ number_format($summary['gross'], 2, ',', '.') }} €</strong></x-filament::section>
        @endif
    </div>

    <x-filament::section>
        @if ($activeTab === 'tax')
            <div class="reports-table-header">
                <div class="reports-export-actions ms-auto">
                    <x-filament::button color="gray" size="sm" icon="heroicon-o-document-arrow-down" wire:click="exportUstvaPdf">PDF</x-filament::button>
                    <x-filament::button color="gray" size="sm" icon="heroicon-o-document-text" wire:click="exportCsv">CSV</x-filament::button>
                </div>
            </div>
        @endif

        @if ($activeTab === 'tax')
            @include('filament.pages.reports-ustva', ['report' => $ustvaReport])
        @else
            {{ $this->table }}
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
