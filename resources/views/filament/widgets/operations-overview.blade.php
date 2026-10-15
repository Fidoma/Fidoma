<x-filament-widgets::widget>
    <div class="dashboard-operations-grid">
        <x-filament::section icon="heroicon-o-link" heading="Dokumente und Belege">
            <div class="dashboard-metric-list">
                <div>
                    <span>Nicht zugeordnete Banktransaktionen</span>
                    <strong>{{ number_format($documents['unmapped_transactions'], 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span>Unverarbeitete Dokumente</span>
                    <strong>{{ $documents['unprocessed_documents'] === null ? '–' : number_format($documents['unprocessed_documents'], 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="dashboard-progress-block">
                <div>
                    <span>Belegzuordnungsquote</span>
                    <strong>{{ number_format($documents['assignment_rate'], 1, ',', '.') }} %</strong>
                </div>
                <div class="dashboard-progress-track" role="progressbar" aria-valuenow="{{ $documents['assignment_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div style="width: {{ min(100, max(0, $documents['assignment_rate'])) }}%"></div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-document-duplicate" heading="Angebote">
            <div class="dashboard-quote-grid">
                <div><span>Offener Angebotswert</span><strong>{{ number_format($quotes['open_value'], 2, ',', '.') }} €</strong></div>
                <div><span>Offene Angebote</span><strong>{{ number_format($quotes['open_count'], 0, ',', '.') }}</strong></div>
                <div><span>Angenommen</span><strong>{{ number_format($quotes['approved_count'], 0, ',', '.') }}</strong></div>
                <div><span>Annahmequote</span><strong>{{ number_format($quotes['acceptance_rate'], 1, ',', '.') }} %</strong></div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
