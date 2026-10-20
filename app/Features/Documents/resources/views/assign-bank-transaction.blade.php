<div x-data="{ activeTab: 'assign' }" class="flex flex-col gap-6 pt-5">
    <x-filament::tabs label="Dokumentzuordnung">
        <x-filament::tabs.item
            alpine-active="activeTab === 'assign'"
            x-on:click="activeTab = 'assign'"
        >
            Zuordnen
        </x-filament::tabs.item>

        <x-filament::tabs.item
            alpine-active="activeTab === 'linked'"
            x-on:click="activeTab = 'linked'; $wire.$dispatch('paperless-linked-tab-opened')"
        >
            Verknüpft
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div x-show="activeTab === 'assign'" class="flex flex-col gap-6">
        {{ $this->documentInfolist }}

        {{ $this->table }}
    </div>

    <div x-show="activeTab === 'linked'" x-cloak>
        @livewire(
            \App\Features\Documents\Livewire\LinkedBankTransactions::class,
            ['documentId' => (int) $document['id']],
            key('linked-paperless-transactions-'.$document['id'])
        )
    </div>

    <x-filament-actions::modals />
</div>
