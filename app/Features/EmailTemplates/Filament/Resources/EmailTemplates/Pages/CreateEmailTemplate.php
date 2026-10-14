<?php

namespace App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Pages;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\EmailTemplates\Services\EmailTemplateDefaults;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    private string $defaultUsage = 'none';

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->defaultUsage = (string) ($data['default_usage'] ?? 'none');
        unset($data['default_usage']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(EmailTemplateDefaults::class)->assign($this->record, $this->defaultUsage);
    }
}
