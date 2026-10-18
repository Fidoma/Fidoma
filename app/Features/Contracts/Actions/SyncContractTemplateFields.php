<?php

namespace App\Features\Contracts\Actions;

use App\Features\Contracts\Models\ContractTemplate;

final class SyncContractTemplateFields
{
    /** @param array<int, array{field_key: string, label: string, field_type: string, can_delete?: bool}> $fields */
    public function handle(ContractTemplate $template, array $fields): void
    {
        $fieldKeys = collect($fields)->pluck('field_key')->all();

        $template->fields()
            ->whereNotIn('field_key', $fieldKeys)
            ->whereDoesntHave('values')
            ->delete();

        foreach ($fields as $sortOrder => $field) {
            $template->fields()->updateOrCreate(
                ['field_key' => $field['field_key']],
                [
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'is_required' => false,
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }
}
