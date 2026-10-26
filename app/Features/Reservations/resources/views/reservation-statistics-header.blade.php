<header class="fi-header dashboard-header">
    <div>
        <h1 class="fi-header-heading">{{ $this->getHeading() }}</h1>
    </div>

    <form wire:submit="applyFilters" class="dashboard-period-filter">
        <x-filament::dropdown placement="bottom-start">
            <x-slot name="trigger">
                <x-filament::button type="button" color="gray" icon="heroicon-o-calendar-days">
                    {{ $this->periodLabel() }}
                </x-filament::button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach (['month', 'quarter', 'year'] as $period)
                    <x-filament::dropdown.list.item
                        :color="$this->selectedPeriod === $period ? 'primary' : 'gray'"
                        :icon="$this->selectedPeriod === $period ? 'heroicon-m-check' : null"
                        role="button"
                        x-on:click="close"
                        wire:click="selectPeriod('{{ $period }}')"
                    >
                        {{ __('reservations::messages.statistics.periods.'.$period) }}
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>

        <div class="dashboard-period-field">
            <label for="reservation-statistics-start">{{ __('reservations::messages.statistics.filters.from') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input id="reservation-statistics-start" type="date" wire:model="startDate" />
            </x-filament::input.wrapper>
        </div>

        <div class="dashboard-period-field">
            <label for="reservation-statistics-end">{{ __('reservations::messages.statistics.filters.until') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input id="reservation-statistics-end" type="date" wire:model="endDate" />
            </x-filament::input.wrapper>
            @error('endDate')
                <span class="dashboard-period-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="dashboard-period-field">
            <label for="reservation-statistics-room">{{ __('reservations::messages.statistics.filters.room') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input.select id="reservation-statistics-room" wire:model.live="roomId">
                    <option value="">{{ __('reservations::messages.statistics.filters.all_rooms') }}</option>
                    @foreach ($this->roomOptions() as $roomId => $room)
                        <option value="{{ $roomId }}">{{ $room }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <div class="dashboard-period-field">
            <label for="reservation-statistics-status">{{ __('reservations::messages.statistics.filters.status') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input.select id="reservation-statistics-status" wire:model.live="status">
                    <option value="">{{ __('reservations::messages.statistics.filters.all_statuses') }}</option>
                    @foreach (\App\Features\Reservations\Enums\ReservationStatus::options() as $statusId => $statusLabel)
                        <option value="{{ $statusId }}">{{ $statusLabel }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
            {{ __('reservations::messages.statistics.filters.apply') }}
        </x-filament::button>
    </form>
</header>
