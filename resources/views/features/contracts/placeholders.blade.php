<div
    class="contract-placeholders-table-wrapper"
    x-data="{
        customPlaceholders: $wire.entangle('data.custom_placeholders'),
        formatPlaceholder(placeholder) {
            return `\u007b\u007b${placeholder.field_type}:${placeholder.field_key}\u007d\u007d`;
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
                <th scope="col" class="contract-placeholder-copy-column">
                    <span class="sr-only">{{ __('contracts::messages.template.placeholders.delete') }}</span>
                </th>
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
                            x-on:click="navigator.clipboard.writeText(formatPlaceholder(customPlaceholder))"
                        />
                    </td>
                    <td><code x-text="formatPlaceholder(customPlaceholder)"></code></td>
                    <td class="contract-placeholder-copy-column">
                        <x-filament::icon-button
                            icon="heroicon-o-trash"
                            color="danger"
                            size="sm"
                            :label="__('contracts::messages.template.placeholders.delete')"
                            x-bind:disabled="! customPlaceholder.can_delete"
                            x-bind:title="customPlaceholder.can_delete
                                ? @js(__('contracts::messages.template.placeholders.delete'))
                                : @js(__('contracts::messages.template.placeholders.cannot_delete'))"
                            x-on:click="if (customPlaceholder.can_delete) {
                                customPlaceholders = customPlaceholders.filter(
                                    (placeholder) => placeholder.field_key !== customPlaceholder.field_key,
                                );
                            }"
                        />
                    </td>
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
                        <td></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
