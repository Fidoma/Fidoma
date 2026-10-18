<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\Concerns;

use App\Features\Contracts\Actions\SyncContractTemplateFields;

trait ManagesContractTemplateFields
{
    /** @var array<int, array{field_key: string, label: string}> */
    private array $customPlaceholders = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractCustomPlaceholders(array $data): array
    {
        $this->customPlaceholders = $data['custom_placeholders'] ?? [];
        unset($data['custom_placeholders']);

        return $data;
    }

    protected function syncCustomPlaceholders(): void
    {
        app(SyncContractTemplateFields::class)->handle($this->record, $this->customPlaceholders);
    }
}
