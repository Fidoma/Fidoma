<?php

namespace App\Features\Families\Filament\Resources\Families\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FamilyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('family_name')
                    ->label('Name')
                    ->maxLength(50),
            ]);
    }
}
