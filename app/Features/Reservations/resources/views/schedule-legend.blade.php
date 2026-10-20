@php
    $rowClass = 'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 border-t border-gray-200 py-3 first:border-t-0 dark:border-white/10';
@endphp

<div class="grid gap-4">
    <x-filament::section compact :heading="__('reservations::messages.schedule.legend.events.heading')" icon="heroicon-o-calendar">
        <div class="{{ $rowClass }}">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('reservations::messages.schedule.legend.events.description') }}</span>
            <x-filament::badge color="gray">{{ __('reservations::messages.schedule.legend.events.badge') }}</x-filament::badge>
        </div>
    </x-filament::section>

    <x-filament::section compact :heading="__('reservations::messages.schedule.legend.vertical_border')">
        <div class="{{ $rowClass }}">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('reservations::messages.schedule.legend.open_description') }}</span>
            <x-filament::badge color="gray">{{ __('reservations::messages.schedule.legend.open') }}</x-filament::badge>
        </div>
        <div class="{{ $rowClass }}">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('reservations::messages.schedule.legend.confirmed_description') }}</span>
            <x-filament::badge color="success">{{ __('reservations::messages.schedule.legend.confirmed') }}</x-filament::badge>
        </div>
        <div class="{{ $rowClass }}">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('reservations::messages.schedule.legend.canceled_description') }}</span>
            <x-filament::badge color="danger">{{ __('reservations::messages.schedule.legend.canceled') }}</x-filament::badge>
        </div>
    </x-filament::section>

    <x-filament::section compact :heading="__('reservations::messages.schedule.legend.top_bar')">
        <div class="{{ $rowClass }}">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('reservations::messages.schedule.legend.unbilled_description') }}</span>
            <x-filament::badge color="gray">{{ __('reservations::messages.schedule.legend.unbilled') }}</x-filament::badge>
        </div>
        <div class="{{ $rowClass }}">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('reservations::messages.schedule.legend.paid_description') }}</span>
            <x-filament::badge color="success">{{ __('reservations::messages.schedule.legend.paid') }}</x-filament::badge>
        </div>
    </x-filament::section>

    <x-filament::section compact :heading="__('reservations::messages.schedule.legend.date_header')">
        <div class="{{ $rowClass }}">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('reservations::messages.schedule.legend.date_header_description') }}</span>
            <x-filament::badge color="warning">{{ __('reservations::messages.schedule.legend.trade_fair_day') }}</x-filament::badge>
        </div>
    </x-filament::section>
</div>
