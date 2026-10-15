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

        <section
            class="invoice-layout-panel invoice-layout-preview"
            x-data="{ previewMode: @js($pdfPreviewUrl ? 'pdf' : 'html') }"
            x-on:invoice-layout-preview-updated.window="
                $refs.previewFrame.removeAttribute('src');
                $refs.previewFrame.setAttribute('sandbox', '');
                $refs.previewFrame.srcdoc = $event.detail.html;
                previewMode = 'html';
            "
        >
            <div class="invoice-layout-preview-title">
                <span x-text="previewMode === 'pdf' ? 'PDF-Vorschau' : 'HTML-Vorschau'"></span>
                <span class="invoice-layout-live" x-show="previewMode === 'html'">Live</span>
            </div>

            <iframe
                x-ref="previewFrame"
                title="Rechnungsvorschau"
                @if ($pdfPreviewUrl)
                    src="{{ $pdfPreviewUrl }}"
                @else
                    sandbox
                    srcdoc="{{ $previewHtml }}"
                @endif
            ></iframe>
        </section>
    </div>
</x-filament-panels::page>
