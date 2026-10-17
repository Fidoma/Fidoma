<x-filament-panels::page>
    @if ($installed)
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <iframe
                src="{{ route('webmail.client') }}"
                title="{{ __('webmail::messages.feature') }}"
                referrerpolicy="same-origin"
                class="block min-h-[560px] w-full border-0"
                style="height: calc(100dvh - 11rem)"
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
