<?php

namespace App\Features\Contracts\Actions;

use App\Features\Contracts\Models\ContractTemplate;

final class SyncContractTemplateFields
{
    /** @param array<int, array{field_key: string, label: string}> $fields */
    public function handle(ContractTemplate $template, array $fields): void
    {
        foreach ($fields as $sortOrder => $field) {
            $template->fields()->updateOrCreate(
                ['field_key' => $field['field_key']],
                [
                    'label' => $field['label'],
                    'field_type' => 'text',
                    'is_required' => false,
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }
}
