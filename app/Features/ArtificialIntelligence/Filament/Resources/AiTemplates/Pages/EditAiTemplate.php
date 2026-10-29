<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages;

use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use App\Features\Settings\Filament\Concerns\HasTemplateTabs;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiTemplate extends EditRecord
{
    use HasTemplateTabs;

    protected static string $resource = AiTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
