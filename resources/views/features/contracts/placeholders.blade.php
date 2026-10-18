<div
    class="contract-placeholders-table-wrapper"
    x-data="{
        customPlaceholders: $wire.entangle('data.custom_placeholders'),
        formatPlaceholder(fieldKey) {
            return `\u007b\u007btext:${fieldKey}\u007d\u007d`;
        },
    }"
>
    <table class="contract-placeholders-table">
        <thead>
            <tr>
                <th scope="col" class="contract-placeholder-copy-column">
                    <span class="sr-only">{{ __('contracts::messages.template.placeholders.copy') }}</span>
                </th>
                <th scope="col">{{ __('contracts::messages.template.placeholders.heading') }}</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="customPlaceholder in customPlaceholders" :key="customPlaceholder.field_key">
                <tr>
                    <td class="contract-placeholder-copy-column">
                        <x-filament::icon-button
                            icon="heroicon-o-clipboard-document"
                            color="gray"
                            size="sm"
                            :label="__('contracts::messages.template.placeholders.copy')"
                            x-on:click="navigator.clipboard.writeText(formatPlaceholder(customPlaceholder.field_key))"
                        />
                    </td>
                    <td><code x-text="formatPlaceholder(customPlaceholder.field_key)"></code></td>
                </tr>
            </template>

            @foreach ($groups as $placeholders)
                @foreach ($placeholders as $placeholder => $type)
                    <tr>
                        <td class="contract-placeholder-copy-column">
                            <x-filament::icon-button
                                icon="heroicon-o-clipboard-document"
                                color="gray"
                                size="sm"
                                :label="__('contracts::messages.template.placeholders.copy')"
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
