<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages;

use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiTemplate extends EditRecord
{
    protected static string $resource = AiTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
