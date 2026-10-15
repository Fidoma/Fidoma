<header class="fi-header dashboard-header">
    <div>
        <h1 class="fi-header-heading">{{ $this->getHeading() }}</h1>
    </div>

    <form wire:submit="applyDateFilter" class="dashboard-period-filter">
        <div class="dashboard-period-field">
            <label for="dashboard-start-date">Von</label>
            <x-filament::input.wrapper>
                <x-filament::input
                    id="dashboard-start-date"
                    type="date"
                    wire:model="startDate"
                />
            </x-filament::input.wrapper>
        </div>

        <div class="dashboard-period-field">
            <label for="dashboard-end-date">Bis</label>
            <x-filament::input.wrapper>
                <x-filament::input
                    id="dashboard-end-date"
                    type="date"
                    wire:model="endDate"
                />
            </x-filament::input.wrapper>
            @error('endDate')
                <span class="dashboard-period-error">{{ $message }}</span>
            @enderror
        </div>

        <x-filament::button
            type="submit"
            icon="heroicon-m-magnifying-glass"
            wire:loading.attr="disabled"
            wire:target="applyDateFilter"
        >
            Suchen
        </x-filament::button>
    </form>
</header>
