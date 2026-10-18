<div class="import-column-mapping">
    <div class="import-column-mapping__header">
        <div>
            <span>Datenbankspalte</span>
            <small>Feste Zielstruktur</small>
        </div>
        <div>
            <span>Erkannte CSV-Spalte</span>
            <small>Automatisch erkannt oder manuell auswählen</small>
        </div>
    </div>

    <div class="import-column-mapping__body">
        @foreach ($mappingRows as $row)
            <div class="import-column-mapping__row">
                <div class="import-column-mapping__target">
                    <span>
                        {{ $row['label'] }}
                        @if ($row['required'])
                            <strong title="Pflichtfeld">*</strong>
                        @endif
                    </span>
                    <code>{{ $row['field'] }}</code>
                </div>

                <div class="import-column-mapping__source">
                    <x-filament::input.wrapper>
                        <x-filament::input.select
                            wire:model="data.mapping.{{ $row['field'] }}"
                            aria-label="CSV-Spalte für {{ $row['label'] }}"
                        >
                            <option value="">
                                Nicht zuordnen{{ $row['defaultValue'] !== null ? ' – Standard: '.$row['defaultValue'] : '' }}
                            </option>
                            @foreach ($csvHeaders as $csvHeader)
                                <option value="{{ $csvHeader }}">{{ $csvHeader }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
    .import-column-mapping {
        overflow: hidden;
        border: 1px solid rgb(229 231 235);
        border-radius: .75rem;
    }

    .import-column-mapping__header,
    .import-column-mapping__row {
        display: grid;
        grid-template-columns: minmax(16rem, 42%) minmax(18rem, 58%);
    }

    .import-column-mapping__header {
        background: rgb(249 250 251);
        border-bottom: 1px solid rgb(229 231 235);
    }

    .import-column-mapping__header > div {
        display: flex;
        flex-direction: column;
        gap: .125rem;
        padding: .875rem 1rem;
    }

    .import-column-mapping__header > div:first-child,
    .import-column-mapping__target {
        border-right: 1px solid rgb(229 231 235);
    }

    .import-column-mapping__header span {
        font-size: .875rem;
        font-weight: 600;
    }

    .import-column-mapping__header small {
        color: rgb(107 114 128);
        font-size: .75rem;
    }

    .import-column-mapping__row:not(:last-child) {
        border-bottom: 1px solid rgb(229 231 235);
    }

    .import-column-mapping__target,
    .import-column-mapping__source {
        min-width: 0;
        padding: .75rem 1rem;
    }

    .import-column-mapping__target {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: rgb(249 250 251 / 60%);
    }

    .import-column-mapping__target span,
    .import-column-mapping__source select {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .import-column-mapping__target strong {
        color: rgb(225 29 72);
    }

    .import-column-mapping__target code {
        color: rgb(107 114 128);
        font-size: .75rem;
        white-space: nowrap;
    }

    .import-column-mapping__source {
        display: block;
    }

    .dark .import-column-mapping,
    .dark .import-column-mapping__header,
    .dark .import-column-mapping__header > div:first-child,
    .dark .import-column-mapping__target,
    .dark .import-column-mapping__row:not(:last-child) {
        border-color: rgb(255 255 255 / 10%);
    }

    .dark .import-column-mapping__header,
    .dark .import-column-mapping__target {
        background: rgb(255 255 255 / 5%);
    }

    @media (max-width: 768px) {
        .import-column-mapping {
            overflow-x: auto;
        }

        .import-column-mapping__header,
        .import-column-mapping__row {
            min-width: 42rem;
        }
    }
</style>
