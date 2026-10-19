<?php

namespace App\Features\Clients\Filament\Resources\Clients;

use App\Features\Clients\Filament\Resources\Clients\Pages\CreateClient;
use App\Features\Clients\Filament\Resources\Clients\Pages\EditClient;
use App\Features\Clients\Filament\Resources\Clients\Pages\ListClients;
use App\Features\Clients\Filament\Resources\Clients\Pages\ViewClient;
use App\Features\Clients\Filament\Resources\Clients\RelationManagers\InvoicesRelationManager;
use App\Features\Clients\Filament\Resources\Clients\RelationManagers\PaymentsRelationManager;
use App\Features\Clients\Filament\Resources\Clients\RelationManagers\QuotesRelationManager;
use App\Features\Clients\Filament\Resources\Clients\Schemas\ClientForm;
use App\Features\Clients\Filament\Resources\Clients\Schemas\ClientInfolist;
use App\Features\Clients\Filament\Resources\Clients\Tables\ClientsTable;
use App\Features\Clients\Models\Client;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationLabel = 'Kunden';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getNavigationLabel(): string
    {
        return __('clients::messages.feature');
    }

    public static function getModelLabel(): string
    {
        return __('clients::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('clients::messages.feature');
    }

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            QuotesRelationManager::class,
            InvoicesRelationManager::class,
            PaymentsRelationManager::class,
        ];
    }
}
