<?php

namespace App\Features\Contracts\Filament\Resources\Contracts;

use App\Features\Contracts\Filament\Resources\Contracts\Pages\CreateContract;
use App\Features\Contracts\Filament\Resources\Contracts\Pages\EditContract;
use App\Features\Contracts\Filament\Resources\Contracts\Pages\ListContracts;
use App\Features\Contracts\Filament\Resources\Contracts\Pages\ViewContract;
use App\Features\Contracts\Filament\Resources\Contracts\Schemas\ContractForm;
use App\Features\Contracts\Filament\Resources\Contracts\Schemas\ContractInfolist;
use App\Features\Contracts\Filament\Resources\Contracts\Tables\ContractsTable;
use App\Features\Contracts\Models\Contract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $modelLabel = 'Vertrag';

    protected static ?string $pluralModelLabel = 'Verträge';

    protected static ?string $navigationLabel = 'Verträge';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'contracts';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationLabel(): string
    {
        return __('contracts::messages.feature');
    }

    public static function getModelLabel(): string
    {
        return __('contracts::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contracts::messages.feature');
    }

    public static function form(Schema $schema): Schema
    {
        return ContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContractInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
            'create' => CreateContract::route('/create'),
            'view' => ViewContract::route('/{record}'),
            'edit' => EditContract::route('/{record}/edit'),
        ];
    }
}
