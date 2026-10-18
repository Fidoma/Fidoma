<x-filament-panels::page>
    <form wire:submit.prevent="importCsv">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
