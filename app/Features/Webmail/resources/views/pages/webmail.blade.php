<x-filament-panels::page :full-height="$installed">
    @if ($installed)
        <div
            class="webmail-shell"
            x-data="{
                observer: null,
                syncTheme() {
                    const frame = this.$refs.frame

                    if (! frame?.contentWindow) return

                    frame.contentWindow.postMessage({
                        type: 'fidoma:theme',
                        dark: document.documentElement.classList.contains('dark'),
                    }, window.location.origin)
                },
                init() {
                    this.observer = new MutationObserver(() => this.syncTheme())
                    this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
                },
                destroy() {
                    this.observer?.disconnect()
                },
            }"
        >
            <iframe
                x-ref="frame"
                x-on:load="syncTheme()"
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
