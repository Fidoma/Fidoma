<div class="csv-preview">
    <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">CSV-Vorschau</h3>

    <div class="csv-preview__scroll">
        <table class="csv-preview__table">
            <thead>
                <tr>
                    @foreach ($csvHeaders as $header)
                        <th @class(['csv-preview__wide' => in_array($header, ['Verwendungszweck', 'Beguenstigter/Zahlungspflichtiger'], true)])>
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($previewRows as $row)
                    <tr>
                        @foreach ($csvHeaders as $header)
                            <td @class(['csv-preview__wide' => in_array($header, ['Verwendungszweck', 'Beguenstigter/Zahlungspflichtiger'], true)])>
                                {{ filled($row[$header] ?? null) ? $row[$header] : '–' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
    .csv-preview {
        min-width: 0;
        max-width: 100%;
    }

    .csv-preview__scroll {
        max-width: 100%;
        max-height: 32rem;
        overflow: auto;
        border: 1px solid rgb(229 231 235);
        border-radius: .75rem;
    }

    .csv-preview__table {
        width: max-content;
        min-width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: .875rem;
        line-height: 1.35rem;
    }

    .csv-preview__table th,
    .csv-preview__table td {
        min-width: 10rem;
        max-width: 18rem;
        padding: .75rem 1rem;
        text-align: left;
        vertical-align: top;
        white-space: nowrap;
        border-right: 1px solid rgb(229 231 235);
        border-bottom: 1px solid rgb(229 231 235);
    }

    .csv-preview__table th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: rgb(249 250 251);
        font-weight: 600;
    }

    .csv-preview__table .csv-preview__wide {
        min-width: 24rem;
        max-width: 32rem;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .csv-preview__table th:last-child,
    .csv-preview__table td:last-child {
        border-right: 0;
    }

    .csv-preview__table tbody tr:last-child td {
        border-bottom: 0;
    }

    .dark .csv-preview__scroll,
    .dark .csv-preview__table th,
    .dark .csv-preview__table td {
        border-color: rgb(255 255 255 / 10%);
    }

    .dark .csv-preview__table th {
        background: rgb(255 255 255 / 5%);
    }
</style>
