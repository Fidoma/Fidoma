<?php

namespace App\Features\Contracts\Filament\Resources\ContractTemplates\Pages;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\Concerns\ManagesContractTemplateFields;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContractTemplate extends EditRecord
{
    use ManagesContractTemplateFields;

    protected static string $resource = ContractTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->contracts()->exists())
                ->tooltip(fn (): ?string => $this->record->contracts()->exists()
                    ? 'Verwendete Vorlagen können nicht gelöscht werden.'
                    : null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['custom_placeholders'] = $this->record->fields()
            ->get(['field_key', 'label'])
            ->map(fn ($field): array => [
                'field_key' => $field->field_key,
                'label' => $field->label,
            ])
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractCustomPlaceholders($data);
    }

    protected function afterSave(): void
    {
        $this->syncCustomPlaceholders();
    }
}
