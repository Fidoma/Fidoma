<?php

namespace App\Features\Invoices\Filament\Resources\Invoices;

use App\Features\Invoices\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Features\Invoices\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Features\Invoices\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Features\Invoices\Models\Invoice;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = Invoice::class;

    protected static function navigationModule(): string
    {
        return 'invoices';
    }

    protected static ?string $modelLabel = 'Rechnung';

    protected static ?string $pluralModelLabel = 'Rechnungen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyEuro;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getModelLabel(): string
    {
        return __('invoices::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('invoices::messages.feature');
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
