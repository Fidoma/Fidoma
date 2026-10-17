<div class="contract-placeholders">
    @foreach ($groups as $title => $placeholders)
        <x-filament::section :heading="$loop->iteration . '. ' . $title" compact>
            <div class="contract-placeholders-table-wrapper">
                <table class="contract-placeholders-table">
                    <thead>
                        <tr>
                            <th scope="col">Platzhalter</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($placeholders as $placeholder => $type)
                            <tr>
                                <td>
                                    <button
                                        type="button"
                                        class="contract-placeholder-button"
                                        title="An der Cursorposition einfügen"
                                        x-on:click="$dispatch('contract-placeholder-insert', { placeholder: @js($placeholder) })"
                                    >
                                        <code>{{ $placeholder }}</code>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endforeach
</div>
