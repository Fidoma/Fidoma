<x-filament-panels::page>
    @vite('resources/js/invoice-layout-editor.js')

    <div
        class="invoice-layout-grid"
    >
        <section
            class="invoice-layout-panel"
            wire:ignore
            x-data="invoiceLayoutEditor(@js($html), @js($css))"
        >
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

            <div class="invoice-layout-editor">
                <div x-show="activeTab === 'html'" x-ref="htmlEditor"></div>
                <div x-show="activeTab === 'css'" x-ref="cssEditor"></div>
            </div>

            <div class="invoice-layout-help">
                <div class="invoice-layout-help-title">Verfügbare Platzhalter</div>
                <div class="invoice-layout-placeholder-table-wrap">
                    <table class="invoice-layout-placeholder-table">
                        <thead>
                            <tr>
                                <th>Platzhalter</th>
                                <th>Beispielwert</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($placeholders as $placeholder)
                                <tr>
                                    <td><code>{{ '{'.'{ '.$placeholder['key'].' }'.'}' }}</code></td>
                                    <td>{{ $placeholder['value'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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
