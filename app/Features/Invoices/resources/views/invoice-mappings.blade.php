<div class="flex flex-col gap-6">
    <x-filament::tabs label="Mapping-Ansicht">
        <x-filament::tabs.item
            :active="$mappingView === 'invoices'"
            icon="heroicon-o-document-currency-euro"
            wire:click="showMappingView('invoices')"
        >
            Rechnungen
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$mappingView === 'payments'"
            icon="heroicon-o-banknotes"
            wire:click="showMappingView('payments')"
        >
            Zahlungen
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{ $this->table }}
</div>
