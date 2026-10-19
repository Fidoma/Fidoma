<header class="fi-header dashboard-header">
    <div>
        <h1 class="fi-header-heading">{{ $this->getHeading() }}</h1>
    </div>

    <form wire:submit="applyDateFilter" class="dashboard-period-filter">
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
