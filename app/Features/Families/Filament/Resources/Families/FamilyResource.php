<?php

namespace App\Features\Families\Filament\Resources\Families;

use App\Features\Families\Filament\Resources\Families\Pages\CreateFamily;
use App\Features\Families\Filament\Resources\Families\Pages\EditFamily;
use App\Features\Families\Filament\Resources\Families\Pages\ListFamilies;
use App\Features\Families\Filament\Resources\Families\Schemas\FamilyForm;
use App\Features\Families\Filament\Resources\Families\Tables\FamiliesTable;
use App\Features\Families\Models\Family;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static ?string $modelLabel = 'Produktfamilie';

    protected static ?string $pluralModelLabel = 'Produktfamilien';

    protected static ?string $navigationLabel = 'Produktfamilien';

    protected static string|UnitEnum|null $navigationGroup = 'Produkte';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return FamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilies::route('/'),
            'create' => CreateFamily::route('/create'),
            'edit' => EditFamily::route('/{record}/edit'),
        ];
    }
}
