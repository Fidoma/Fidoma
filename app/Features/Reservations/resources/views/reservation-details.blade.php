<div class="flex flex-col gap-6">
    <x-filament::section compact>
        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="mb-2 text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('reservations::messages.singular') }}
                    </div>
                    <div class="break-words text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $reservation->reservation_number }}
                    </div>
                    <div class="mt-2 break-words text-sm text-gray-600 dark:text-gray-300">
                        {{ $reservation->client?->client_name ?: '–' }}
                    </div>
                </div>
                <x-filament::badge :color="$reservation->reservation_status_id->color()">
                    {{ $reservation->reservation_status_id->label() }}
                </x-filament::badge>
            </div>

            <dl class="grid grid-cols-2 gap-4 border-t border-gray-200 pt-5 dark:border-white/10">
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('reservations::messages.details.created_at') }}</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $reservation->reservation_date_created?->format('d.m.Y') ?: '–' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('reservations::messages.details.expires_at') }}</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $reservation->reservation_date_expires?->format('d.m.Y') ?: '–' }}</dd>
                </div>
            </dl>
        </div>
    </x-filament::section>

    <x-filament::section :heading="__('reservations::messages.details.stay')" icon="heroicon-o-calendar-days" compact>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('reservations::messages.details.arrival') }}</div>
                <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->arrivalDate()?->format('d.m.Y') ?: '–' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('reservations::messages.details.departure') }}</div>
                <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $this->departureDate()?->format('d.m.Y') ?: '–' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('reservations::messages.details.duration') }}</div>
                <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                    {{ $this->nights() === null ? '–' : trans_choice('reservations::messages.details.nights', $this->nights(), ['count' => $this->nights()]) }}
                </div>
            </div>
            <div>
                <label for="reservation-status-{{ $reservation->getKey() }}" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('reservations::messages.details.status') }}
                </label>
                <x-filament::input.wrapper class="mt-2">
                    <x-filament::input.select
                        id="reservation-status-{{ $reservation->getKey() }}"
                        wire:model.live="reservationStatus"
                        wire:loading.attr="disabled"
                        wire:target="reservationStatus"
                        :disabled="! \App\Features\Reservations\Filament\Resources\Reservations\ReservationResource::canEdit($reservation)"
                    >
                        @foreach (\App\Features\Reservations\Enums\ReservationStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section :heading="__('reservations::messages.details.services')" icon="heroicon-o-building-office" compact>
        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($reservation->items as $item)
                <div class="py-4 first:pt-0 last:pb-0" wire:key="reservation-item-{{ $item->getKey() }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $item->item_name }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                @if ($item->room)
                                    <span>{{ $item->room->displayName() }}</span>
                                @endif
                                @if ($item->arrival_date && $item->departure_date)
                                    <span>{{ $item->arrival_date->format('d.m.Y') }}–{{ $item->departure_date->format('d.m.Y') }}</span>
                                @endif
                                <span>{{ Number::format((float) $item->item_quantity, locale: app()->getLocale()) }} × {{ Number::currency((float) $item->item_price, 'EUR', app()->getLocale()) }}</span>
                            </div>
                            @if (filled($item->item_description))
                                <div class="mt-2 break-words text-sm text-gray-500 dark:text-gray-400">{{ $item->item_description }}</div>
                            @endif
                        </div>
                        <div class="shrink-0 font-semibold tabular-nums text-gray-950 dark:text-white">
                            {{ Number::currency((float) ($item->amount?->item_total ?? 0), 'EUR', app()->getLocale()) }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center gap-3 py-6 text-center">
                    <div class="rounded-full bg-gray-50 p-3 dark:bg-white/5">
                        <x-filament::icon icon="heroicon-o-building-office" class="h-6 w-6 text-gray-400" />
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reservations::messages.details.no_services') }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-5 flex items-end justify-between border-t border-gray-200 pt-5 dark:border-white/10">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('reservations::messages.details.total') }}</span>
            <span class="text-2xl font-semibold tracking-tight tabular-nums text-gray-950 dark:text-white">
                {{ Number::currency((float) ($reservation->amount?->reservation_total ?? 0), 'EUR', app()->getLocale()) }}
            </span>
        </div>
    </x-filament::section>

    @if (filled($reservation->notes))
        <x-filament::section :heading="__('reservations::messages.details.notes')" icon="heroicon-o-document-text" compact>
            <p class="whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $reservation->notes }}</p>
        </x-filament::section>
    @endif

    <x-filament::section :heading="__('reservations::messages.details.billing')" icon="heroicon-o-document-currency-euro" compact>
        @if ($reservation->invoice)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-medium text-gray-950 dark:text-white">{{ $reservation->invoice->invoice_number ?: __('reservations::messages.details.invoice_draft') }}</div>
                    <div class="mt-1 flex flex-wrap gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ $reservation->invoice->invoice_status_id->label() }}</span>
                        <span>·</span>
                        <span>{{ Number::currency((float) ($reservation->invoice->amount?->invoice_total ?? 0), 'EUR', app()->getLocale()) }}</span>
                    </div>
                </div>
                <x-filament::button tag="a" :href="\App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource::getUrl('edit', ['record' => $reservation->invoice])" color="gray" icon="heroicon-o-arrow-top-right-on-square">
                    {{ __('reservations::messages.details.open_invoice') }}
                </x-filament::button>
            </div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reservations::messages.details.not_billed') }}</p>
                {{ $this->createInvoiceAction }}
            </div>
        @endif
    </x-filament::section>

    <x-filament::section :heading="__('reservations::messages.details.more_actions')" :contained="false" compact>
        <div class="flex flex-wrap gap-3">
            @if (\App\Features\Reservations\Filament\Resources\Reservations\ReservationResource::canEdit($reservation))
                <x-filament::button tag="a" :href="\App\Features\Reservations\Filament\Resources\Reservations\ReservationResource::getUrl('edit', ['record' => $reservation])" color="gray" icon="heroicon-o-pencil-square">
                    {{ __('reservations::messages.details.edit') }}
                </x-filament::button>
            @endif
            {{ $this->deleteReservationAction }}
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</div>
