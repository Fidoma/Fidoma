<?php

namespace App\Features\TaxRates\Filament\Resources\TaxRates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaxRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tax_rate_name')
                    ->label('Name')
                    ->required()
                    ->maxLength(25),
                TextInput::make('tax_rate_percent')
                    ->label('Steuersatz')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('%'),
            ]);
    }
}
