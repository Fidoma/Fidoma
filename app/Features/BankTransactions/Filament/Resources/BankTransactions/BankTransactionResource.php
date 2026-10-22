<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\Pages\CreateBankTransaction;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\Pages\EditBankTransaction;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\Pages\ListBankTransactions;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\Schemas\BankTransactionForm;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\Schemas\BankTransactionInfolist;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\Tables\BankTransactionsTable;
use App\Features\BankTransactions\Models\BankTransaction;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BankTransactionResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = BankTransaction::class;

    protected static function navigationModule(): string
    {
        return 'bank_transactions';
    }

    protected static ?string $modelLabel = 'Banktransaktion';

    protected static ?string $pluralModelLabel = 'Banktransaktionen';

    protected static ?string $navigationLabel = 'Banktransaktionen';

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    public static function getNavigationLabel(): string
    {
        return __('bank-transactions::messages.feature');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('bank-transactions::messages.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('bank-transactions::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('bank-transactions::messages.feature');
    }

    public static function form(Schema $schema): Schema
    {
        return BankTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BankTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankTransactions::route('/'),
            'create' => CreateBankTransaction::route('/create'),
            'edit' => EditBankTransaction::route('/{record}/edit'),
        ];
    }
}
