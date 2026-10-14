<?php

namespace App\Features\Units\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('unit_name')->label('Einzahl')->maxLength(50),
            TextInput::make('unit_name_plrl')->label('Mehrzahl')->maxLength(50),
        ]);
    }
}
