<x-filament::section>
    <x-slot name="heading">{{ __('reservations::messages.statistics.rooms.heading') }}</x-slot>
    <x-slot name="description">{{ __('reservations::messages.statistics.rooms.description') }}</x-slot>

    <div class="overflow-x-auto">
        <table class="w-full table-auto text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left dark:border-white/10">
                    <th class="px-3 py-3 font-medium">{{ __('reservations::messages.statistics.rooms.room') }}</th>
                    <th class="px-3 py-3 text-right font-medium">{{ __('reservations::messages.statistics.rooms.occupied') }}</th>
                    <th class="px-3 py-3 text-right font-medium">{{ __('reservations::messages.statistics.rooms.available') }}</th>
                    <th class="px-3 py-3 text-right font-medium">{{ __('reservations::messages.statistics.rooms.unused') }}</th>
                    <th class="px-3 py-3 text-right font-medium">{{ __('reservations::messages.statistics.rooms.rate') }}</th>
                    <th class="px-3 py-3 text-right font-medium">{{ __('reservations::messages.statistics.rooms.conflicts') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                        <td class="px-3 py-3 font-medium">
                            {{ $room['room'] }}
                            @if (! $room['active'])
                                <x-filament::badge color="gray">{{ __('reservations::messages.statistics.rooms.inactive') }}</x-filament::badge>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right">{{ number_format($room['occupied'], 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($room['available'], 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($room['unused'], 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($room['occupancy_rate'], 1, ',', '.') }} %</td>
                        <td class="px-3 py-3 text-right">
                            @if ($room['conflicts'] > 0)
                                <x-filament::badge color="danger">{{ $room['conflicts'] }}</x-filament::badge>
                            @else
                                0
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">{{ __('reservations::messages.statistics.rooms.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::section>
