<?php

namespace App\Features\TaxRates\Filament\Resources\TaxRates;

use App\Features\TaxRates\Filament\Resources\TaxRates\Pages\CreateTaxRate;
use App\Features\TaxRates\Filament\Resources\TaxRates\Pages\EditTaxRate;
use App\Features\TaxRates\Filament\Resources\TaxRates\Pages\ListTaxRates;
use App\Features\TaxRates\Filament\Resources\TaxRates\Schemas\TaxRateForm;
use App\Features\TaxRates\Filament\Resources\TaxRates\Tables\TaxRatesTable;
use App\Features\TaxRates\Models\TaxRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TaxRateResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = TaxRate::class;

    protected static ?string $modelLabel = 'Steuersatz';

    protected static ?string $pluralModelLabel = 'Steuersätze';

    protected static ?string $navigationLabel = 'Steuersätze';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function form(Schema $schema): Schema
    {
        return TaxRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxRatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxRates::route('/'),
            'create' => CreateTaxRate::route('/create'),
            'edit' => EditTaxRate::route('/{record}/edit'),
        ];
    }
}
