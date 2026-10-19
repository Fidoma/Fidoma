<div class="grid gap-4">
    <x-filament::section compact :heading="__('reservations::messages.schedule.legend.vertical_border')">
        <div class="flex flex-wrap gap-2">
            <x-filament::badge color="gray">{{ __('reservations::messages.schedule.legend.open') }}</x-filament::badge>
            <x-filament::badge color="success">{{ __('reservations::messages.schedule.legend.confirmed') }}</x-filament::badge>
            <x-filament::badge color="danger">{{ __('reservations::messages.schedule.legend.canceled') }}</x-filament::badge>
        </div>
    </x-filament::section>

    <x-filament::section compact :heading="__('reservations::messages.schedule.legend.top_bar')">
        <div class="flex flex-wrap gap-2">
            <x-filament::badge color="gray">{{ __('reservations::messages.schedule.legend.unbilled') }}</x-filament::badge>
            <x-filament::badge color="success">{{ __('reservations::messages.schedule.legend.paid') }}</x-filament::badge>
        </div>
    </x-filament::section>
</div>
