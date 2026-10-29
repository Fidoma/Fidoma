<?php

namespace App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Pages;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\Settings\Filament\Concerns\HasTemplateTabs;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    use HasTemplateTabs;
    use WithoutPageHeading;

    protected static string $resource = EmailTemplateResource::class;
}
