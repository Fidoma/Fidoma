<div class="ustva-details">
    <p class="ustva-details-description">{{ $description }}</p>
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
