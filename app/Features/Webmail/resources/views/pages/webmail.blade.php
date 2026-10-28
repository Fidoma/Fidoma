<x-filament-panels::page :full-height="$installed">
    @if ($installed)
        <div
            class="webmail-shell"
            x-data="{
                observer: null,
                messageHandler: null,
                syncTheme() {
                    const frame = this.$refs.frame

                    if (! frame?.contentWindow) return

                    frame.contentWindow.postMessage({
                        type: 'fidoma:theme',
                        dark: document.documentElement.classList.contains('dark'),
                    }, window.location.origin)
                },
                async syncAiTemplates() {
                    const frame = this.$refs.frame

                    if (! frame?.contentWindow) return

                    const templates = await this.$wire.emailMessageAiTemplates()

                    frame.contentWindow.postMessage({
                        type: 'fidoma:ai-templates',
                        label: @js(__('artificial-intelligence::messages.roundcube.actions')),
                        templates,
                    }, window.location.origin)
                },
                syncFrame() {
                    this.syncTheme()
                    this.syncAiTemplates()
                },
                init() {
                    this.observer = new MutationObserver(() => this.syncTheme())
                    this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

                    this.messageHandler = (event) => {
                        if (event.origin !== window.location.origin || event.source !== this.$refs.frame?.contentWindow) return
                        if (! event.data || typeof event.data.type !== 'string') return

                        if (event.data.type === 'fidoma:ai-templates-request') {
                            this.syncAiTemplates()

                            return
                        }

                        if (event.data.type !== 'fidoma:ai-template-selected'
                            || ! Number.isInteger(event.data.templateId)
                            || typeof event.data.context !== 'object'
                            || event.data.context === null) return

                        this.$wire.prepareAiTemplate(event.data.templateId, event.data.context)
                    }

                    window.addEventListener('message', this.messageHandler)
                },
                destroy() {
                    this.observer?.disconnect()
                    window.removeEventListener('message', this.messageHandler)
                },
            }"
        >
            <iframe
                x-ref="frame"
                x-on:load="syncFrame()"
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
