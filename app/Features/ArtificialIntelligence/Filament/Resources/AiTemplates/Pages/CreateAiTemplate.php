<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages;

use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use App\Features\Settings\Filament\Concerns\HasTemplateTabs;
use App\Filament\Resources\Pages\CreateRecord;

class CreateAiTemplate extends CreateRecord
{
    use HasTemplateTabs;

    protected static string $resource = AiTemplateResource::class;
}
