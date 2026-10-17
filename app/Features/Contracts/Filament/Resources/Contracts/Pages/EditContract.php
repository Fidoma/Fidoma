<?php

namespace App\Features\Contracts\Filament\Resources\Contracts\Pages;

use App\Features\Contracts\Filament\Resources\Contracts\ContractResource;
use App\Features\Contracts\Models\ContractTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    /** @var array<string, mixed> */
    private array $customFields = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $values = $this->record->fieldValues()->pluck('value', 'template_field_id');
        $data['custom_fields'] = $this->record->template->fields
            ->mapWithKeys(fn ($field): array => [$field->field_key => $values[$field->getKey()] ?? $field->default_value])
            ->all();

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        return $data;
    }

    protected function afterSave(): void
    {
        $template = ContractTemplate::query()->with('fields')->findOrFail($this->record->template_id);
        $this->record->fieldValues()->delete();

        foreach ($template->fields as $field) {
            $value = $this->customFields[$field->field_key] ?? null;
            $this->record->fieldValues()->create([
                'template_field_id' => $field->getKey(),
                'value' => is_bool($value) ? (string) (int) $value : $value,
            ]);
        }
    }
}
