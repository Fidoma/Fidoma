<?php

namespace App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Pages;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\EmailTemplates\Services\EmailTemplateDefaults;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    private string $defaultUsage = 'none';

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['default_usage'] = app(EmailTemplateDefaults::class)->contextFor($this->record);

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->defaultUsage = (string) ($data['default_usage'] ?? 'none');
        unset($data['default_usage']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(EmailTemplateDefaults::class)->assign($this->record, $this->defaultUsage);
    }
}
