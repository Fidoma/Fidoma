<?php

namespace App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\Pages;

use App\Features\ArtificialIntelligence\Filament\Resources\AiTemplates\AiTemplateResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListAiTemplates extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = AiTemplateResource::class;
}
