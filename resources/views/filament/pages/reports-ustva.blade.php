<div class="ustva-report">
    <div class="ustva-status {{ $report['complete'] ? 'is-complete' : 'is-incomplete' }}">
        <x-filament::icon :icon="$report['complete'] ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle'" />
        <div>
            <strong>{{ $report['complete'] ? 'Auswertung vollständig' : 'Auswertung unvollständig' }}</strong>
            <span>Nur eindeutig aus den vorhandenen Fidoma-Daten ableitbare Beträge werden berechnet.</span>
        </div>
    </div>

    <div class="ustva-warnings">
        @foreach ($report['warnings'] as $warning)
            <div><x-filament::icon icon="heroicon-o-information-circle" /> <span>{{ $warning }}</span></div>
        @endforeach
    </div>

    <div class="ustva-table-wrap">
        <table class="ustva-table">
            <thead><tr><th>Kz.</th><th>Beschreibung</th><th>Bemessungsgrundlage</th><th>Steuerbetrag</th><th>Status</th></tr></thead>
            <tbody>
                @foreach (['Steuerpflichtige Umsätze', 'Steuerfreie Umsätze', 'Weitere steuerliche Sachverhalte', 'Abziehbare Vorsteuerbeträge'] as $section)
                    @php($sectionRows = collect($report['rows'])->where('section', $section))
                    <tr class="ustva-section-row"><th colspan="5">{{ $section }}</th></tr>
                    @forelse ($sectionRows as $row)
                        <tr
                            @class(['ustva-data-row', 'is-clickable' => $row['supported'] && count($row['details'])])
                            @if ($row['supported'] && count($row['details']))
                                wire:click="mountAction('viewUstvaDetails', { code: '{{ $row['code'] }}' })"
                            @endif
                        >
                            <td><strong>{{ $row['code'] }}</strong></td>
                            <td>{{ $row['description'] }}</td>
                            <td>{{ $row['supported'] && $row['basis'] ? number_format($row['net'], 2, ',', '.').' €' : '–' }}</td>
                            <td>{{ $row['supported'] && $row['tax'] ? number_format($row['tax'], 2, ',', '.').' €' : '–' }}</td>
                            <td><x-filament::badge :color="$row['supported'] ? 'success' : 'gray'">{{ $row['supported'] ? 'Berechnet' : 'Nicht verfügbar' }}</x-filament::badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="ustva-empty-section">Noch keine eindeutig ableitbaren Sachverhalte verfügbar.</td></tr>
                    @endforelse
                @endforeach
            </tbody>
        </table>
    </div>

    <h3 class="ustva-summary-heading">Berechnung der Umsatzsteuer-Zahllast</h3>
    <div class="ustva-summary">
        <div><span>Umsatzsteuer gesamt</span><strong>{{ number_format($report['output_tax'], 2, ',', '.') }} €</strong></div>
        <div><span>Abziehbare Vorsteuer gesamt</span><strong>Nicht verfügbar</strong></div>
        <div><span>Weitere Steuerbeträge und Berichtigungen</span><strong>Nicht verfügbar</strong></div>
        <div class="ustva-liability"><span>Rechnerische Zahllast / Überschuss</span><strong>Nicht berechenbar</strong></div>
    </div>
</div>
