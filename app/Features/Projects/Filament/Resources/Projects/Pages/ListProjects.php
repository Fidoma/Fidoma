<?php

namespace App\Features\Projects\Filament\Resources\Projects\Pages;

use App\Features\Projects\Filament\Resources\Projects\ProjectResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = ProjectResource::class;
}
