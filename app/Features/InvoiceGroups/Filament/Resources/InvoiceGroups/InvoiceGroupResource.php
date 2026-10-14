<?php

namespace App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups;

use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Pages\CreateInvoiceGroup;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Pages\EditInvoiceGroup;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Pages\ListInvoiceGroups;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Schemas\InvoiceGroupForm;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\Tables\InvoiceGroupsTable;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InvoiceGroupResource extends Resource
{
    protected static ?string $model = InvoiceGroup::class;

    protected static ?string $modelLabel = 'Nummernkreis';

    protected static ?string $pluralModelLabel = 'Nummernkreise';

    protected static ?string $navigationLabel = 'Nummernkreise';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    public static function form(Schema $schema): Schema
    {
        return InvoiceGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoiceGroups::route('/'),
            'create' => CreateInvoiceGroup::route('/create'),
            'edit' => EditInvoiceGroup::route('/{record}/edit'),
        ];
    }
}
