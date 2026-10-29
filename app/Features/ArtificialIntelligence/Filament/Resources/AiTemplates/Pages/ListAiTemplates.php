<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages;

use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use App\Features\Settings\Filament\Concerns\HasTemplateTabs;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListAiTemplates extends ListRecords
{
    use HasTemplateTabs;
    use WithoutPageHeading;

    protected static string $resource = AiTemplateResource::class;
}
