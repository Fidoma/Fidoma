<div class="ustva-details">
    <p class="ustva-details-description">{{ $description }}</p>
    @if ($excludedDraftCount > 0)
        <div class="ustva-detail-warning">
            {{ $excludedDraftCount }} {{ $excludedDraftCount === 1 ? 'Position wurde' : 'Positionen wurden' }} nicht berücksichtigt, weil die zugehörige Rechnung noch ein Entwurf ist.
        </div>
    @endif
    @livewire(
        \App\Features\Reports\Livewire\UstvaDetailsTable::class,
        [
            'code' => $code,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'taxationType' => $taxationType,
        ],
        key('ustva-details-'.$code.'-'.$startDate.'-'.$endDate.'-'.$taxationType)
    )
</div>
