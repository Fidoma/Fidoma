<?php

namespace App\Features\EmailTemplates\Filament\Resources\EmailTemplates\Pages;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = EmailTemplateResource::class;
}
