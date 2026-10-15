<x-filament-panels::page>
    @vite('resources/js/invoice-layout-editor.js')

    <div
        class="invoice-layout-grid"
        x-data="invoiceLayoutEditor(@js($html), @js($css))"
    >
        <section class="invoice-layout-panel">
            <div class="invoice-layout-toolbar">
                <div class="invoice-layout-tabs" role="tablist" aria-label="Codeart">
                    <button type="button" :class="{ 'is-active': activeTab === 'html' }" @click="activeTab = 'html'">HTML</button>
                    <button type="button" :class="{ 'is-active': activeTab === 'css' }" @click="activeTab = 'css'">CSS</button>
                </div>

                <div class="invoice-layout-actions">
                    <x-filament::button color="gray" icon="heroicon-o-document-magnifying-glass" x-on:click="showPdf()">
                        PDF-Vorschau
                    </x-filament::button>
                    <x-filament::button icon="heroicon-o-check" x-on:click="save()">
                        Speichern
                    </x-filament::button>
                </div>
            </div>

            <div class="invoice-layout-editor" wire:ignore>
                <div x-show="activeTab === 'html'" x-ref="htmlEditor"></div>
                <div x-show="activeTab === 'css'" x-ref="cssEditor"></div>
            </div>

            <details class="invoice-layout-help">
                <summary>Verfügbare Platzhalter</summary>
                <p>
                    <code>@{{ invoice.number }}</code>, <code>@{{ invoice.date }}</code>, <code>@{{ invoice.due_date }}</code>,
                    <code>@{{ client.company }}</code>, <code>@{{ client.name }}</code>, <code>@{{ client.address }}</code>,
                    <code>@{{ items }}</code>, <code>@{{ totals.subtotal }}</code>, <code>@{{ totals.tax }}</code> und
                    <code>@{{ totals.total }}</code>.
                </p>
            </details>
        </section>

        <section class="invoice-layout-panel invoice-layout-preview">
            <div class="invoice-layout-preview-title">
                <span x-text="$wire.pdfPreviewUrl ? 'PDF-Vorschau' : 'HTML-Vorschau'"></span>
                <span class="invoice-layout-live" x-show="! $wire.pdfPreviewUrl">Live</span>
            </div>

            @if ($pdfPreviewUrl)
                <iframe title="PDF-Vorschau" src="{{ $pdfPreviewUrl }}"></iframe>
            @else
                <iframe title="HTML-Vorschau" sandbox srcdoc="{{ $previewHtml }}"></iframe>
            @endif
        </section>
    </div>
</x-filament-panels::page>
