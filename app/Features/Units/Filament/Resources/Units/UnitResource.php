<?php

namespace App\Features\Units\Filament\Resources\Units;

use App\Features\Products\Filament\Clusters\ProductsCluster;
use App\Features\Units\Filament\Resources\Units\Pages\CreateUnit;
use App\Features\Units\Filament\Resources\Units\Pages\EditUnit;
use App\Features\Units\Filament\Resources\Units\Pages\ListUnits;
use App\Features\Units\Filament\Resources\Units\Schemas\UnitForm;
use App\Features\Units\Filament\Resources\Units\Tables\UnitsTable;
use App\Features\Units\Models\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $modelLabel = 'Einheit';

    protected static ?string $pluralModelLabel = 'Einheiten';

    protected static ?string $navigationLabel = 'Einheiten';

    protected static ?string $cluster = ProductsCluster::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
