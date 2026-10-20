@php($counts = $this->counts())

<div class="flex flex-col gap-4">
    <x-filament::tabs label="CSV-Importstatus">
        <x-filament::tabs.item
            :active="$activeTab === 'all'"
            wire:click="showTab('all')"
            :badge="$counts['all']"
        >
            Alle
        </x-filament::tabs.item>
        <x-filament::tabs.item
            :active="$activeTab === 'new'"
            wire:click="showTab('new')"
            :badge="$counts['new']"
        >
            Neu
        </x-filament::tabs.item>
        <x-filament::tabs.item
            :active="$activeTab === 'duplicates'"
            wire:click="showTab('duplicates')"
            :badge="$counts['duplicates']"
        >
            Mögliche Duplikate
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{ $this->table }}
</div>
