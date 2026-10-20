<?php

namespace App\Features\Users\Filament\Resources\Users\Pages;

use App\Features\Users\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
