<div>
    @if ($conflicts->isNotEmpty())
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
        <x-slot name="heading">
            {{ trans_choice('reservations::messages.statistics.conflicts.heading', $conflicts->count(), ['count' => $conflicts->count()]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('reservations::messages.statistics.conflicts.description') }}
        </x-slot>

        <div class="flex flex-wrap gap-2">
            @foreach ($conflicts->take(8) as $conflict)
                <x-filament::badge color="danger">
                    {{ $conflict['room'] }} · {{ \Carbon\CarbonImmutable::parse($conflict['starts_on'])->format('d.m.Y') }}–{{ \Carbon\CarbonImmutable::parse($conflict['ends_on'])->format('d.m.Y') }}
                </x-filament::badge>
            @endforeach
        </div>
        </x-filament::section>
    @endif
</div>
