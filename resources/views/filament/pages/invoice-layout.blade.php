<x-filament-panels::page>
    @vite('resources/js/invoice-layout-editor.js')

    <x-filament::tabs label="Belegart">
        <x-filament::tabs.item
            tag="a"
            :href="\App\Filament\Pages\InvoiceLayout::getUrl(['type' => 'invoice'])"
            :active="$activeType === 'invoice'"
            icon="heroicon-o-document-text"
        >
            Rechnungen
        </x-filament::tabs.item>
        <x-filament::tabs.item
            tag="a"
            :href="\App\Filament\Pages\InvoiceLayout::getUrl(['type' => 'quote'])"
            :active="$activeType === 'quote'"
            icon="heroicon-o-document-duplicate"
        >
            Angebote
        </x-filament::tabs.item>
        <x-filament::tabs.item
            tag="a"
            :href="\App\Filament\Pages\InvoiceLayout::getUrl(['type' => 'reservation'])"
            :active="$activeType === 'reservation'"
            icon="heroicon-o-calendar-days"
        >
            Reservierungen
        </x-filament::tabs.item>
    </x-filament::tabs>

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

    <x-filament::section heading="Verfügbare Platzhalter" class="invoice-layout-placeholder-panel">
        <div class="invoice-layout-placeholder-table-wrap">
            <table class="invoice-layout-placeholder-table">
                <thead>
                    <tr>
                        <th class="invoice-layout-placeholder-copy-column"><span class="sr-only">Kopieren</span></th>
                        <th>Platzhalter</th>
                        <th>Beispielwert</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($placeholders as $placeholder)
                        <tr>
                            <td class="invoice-layout-placeholder-copy-column">
                                <x-filament::icon-button
                                    icon="heroicon-o-clipboard-document"
                                    color="gray"
                                    size="sm"
                                    label="Platzhalter kopieren"
                                    x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from('{'.'{ '.$placeholder['key'].' }'.'}') }})"
                                />
                            </td>
                            <td><code>{{ '{'.'{ '.$placeholder['key'].' }'.'}' }}</code></td>
                            <td>{{ $placeholder['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
