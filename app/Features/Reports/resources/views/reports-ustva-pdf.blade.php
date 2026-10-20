<!doctype html><html lang="de"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#111827;font-size:10px}h1{font-size:18px;margin:0 0 4px}.meta{color:#6b7280;margin-bottom:18px}.warning{padding:8px;background:#fff7ed;border:1px solid #fdba74;margin-bottom:12px}table{width:100%;border-collapse:collapse}th,td{padding:7px;border:1px solid #d1d5db;text-align:left}th{background:#f3f4f6}.number{text-align:right}.section th{background:#e5e7eb}.summary{margin-top:16px;margin-left:auto;width:55%}.summary div{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #e5e7eb}
</style></head><body>
<h1>Umsatzsteuer-Voranmeldung – Vorschau</h1><div class="meta">Zeitraum {{ $start->format('d.m.Y') }}–{{ $end->format('d.m.Y') }} · {{ $report['taxation_type'] === 'soll' ? 'Sollversteuerung' : 'Istversteuerung' }}</div>
<div class="warning"><strong>Unvollständige Vorschau:</strong> {{ implode(' ', $report['warnings']) }}</div>
<table><thead><tr><th>Kz.</th><th>Beschreibung</th><th>Bemessungsgrundlage</th><th>Steuerbetrag</th></tr></thead><tbody>
@foreach(['Steuerpflichtige Umsätze', 'Steuerfreie Umsätze', 'Weitere steuerliche Sachverhalte', 'Abziehbare Vorsteuerbeträge'] as $section)
<tr class="section"><th colspan="4">{{ $section }}</th></tr>
@forelse(collect($report['rows'])->where('section', $section) as $row)
<tr><td>{{ $row['code'] }}</td><td>{{ $row['description'] }}</td><td class="number">{{ $row['supported'] && $row['basis'] ? number_format($row['net'],2,',','.').' €' : '–' }}</td><td class="number">{{ $row['supported'] && $row['tax'] ? number_format($row['tax'],2,',','.').' €' : '–' }}</td></tr>
@empty
<tr><td colspan="4">Noch keine eindeutig ableitbaren Sachverhalte verfügbar.</td></tr>
@endforelse
@endforeach
</tbody></table>
<div class="summary"><div><span>Umsatzsteuer gesamt</span><strong>{{ number_format($report['output_tax'],2,',','.') }} €</strong></div><div><span>Abziehbare Vorsteuer</span><strong>Nicht verfügbar</strong></div><div><span>Zahllast / Überschuss</span><strong>Nicht berechenbar</strong></div></div>
</body></html>
