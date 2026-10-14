<?php

namespace App\Features\Users\Filament\Resources\Users\Pages;

use App\Features\Users\Filament\Resources\Users\UserResource;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = UserResource::class;
}
