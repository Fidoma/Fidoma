<div x-data="{ activeTab: 'assign' }" class="flex flex-col gap-6 pt-5">
    <x-filament::tabs label="Rechnungszuordnung">
        <x-filament::tabs.item
            alpine-active="activeTab === 'assign'"
            x-on:click="activeTab = 'assign'"
        >
            Zuordnen
        </x-filament::tabs.item>

        <x-filament::tabs.item
            alpine-active="activeTab === 'linked'"
            x-on:click="activeTab = 'linked'"
        >
            Verknüpft
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div x-show="activeTab === 'assign'" class="flex flex-col gap-6">
        {{ $this->invoiceInfolist }}
        {{ $this->table }}
    </div>

    <div x-show="activeTab === 'linked'" x-cloak>
        @livewire(
            \App\Features\Invoices\Livewire\LinkedInvoiceBankTransactions::class,
            ['invoiceId' => $invoice->getKey()],
            key('linked-invoice-transactions-'.$invoice->getKey())
        )
    </div>

    <x-filament-actions::modals />
</div>
