<x-filament-panels::page :full-height="$installed">
    @if ($installed)
        <div class="webmail-shell">
            <iframe
                src="{{ route('webmail.client') }}"
                title="{{ __('webmail::messages.feature') }}"
                referrerpolicy="same-origin"
                class="webmail-frame"
            ></iframe>
        </div>
    @else
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('webmail::messages.not_installed') }}
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
