<x-filament::section heading="Platzhalter" compact>
    <div class="contract-placeholders-table-wrapper">
        <table class="contract-placeholders-table">
            <thead>
                <tr>
                    <th scope="col" class="contract-placeholder-copy-column">
                        <span class="sr-only">Kopieren</span>
                    </th>
                    <th scope="col">Platzhalter</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $placeholders)
                    @foreach ($placeholders as $placeholder => $type)
                        <tr>
                            <td class="contract-placeholder-copy-column">
                                <x-filament::icon-button
                                    icon="heroicon-o-clipboard-document"
                                    color="gray"
                                    size="sm"
                                    label="Platzhalter kopieren"
                                    x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($placeholder) }})"
                                />
                            </td>
                            <td><code>{{ $placeholder }}</code></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>
