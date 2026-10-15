<x-filament-panels::page>
    <x-filament::tabs label="Zuordnungsart">
        <x-filament::tabs.item
            :active="$activeTab === 'income'"
            icon="heroicon-o-arrow-down-left"
            wire:click="showTab('income')"
        >
            Einnahmen
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'expenses'"
            icon="heroicon-o-arrow-up-right"
            wire:click="showTab('expenses')"
        >
            Ausgaben
        </x-filament::tabs.item>
    </x-filament::tabs>

    @if ($activeTab === 'income')
        @livewire(\App\Features\Invoices\Livewire\InvoiceMappings::class)
    @else
        {{ $this->table }}
    @endif
</x-filament-panels::page>
