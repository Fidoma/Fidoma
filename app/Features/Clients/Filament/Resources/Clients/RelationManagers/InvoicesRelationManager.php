<?php

namespace App\Features\Clients\Filament\Resources\Clients\RelationManagers;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Rechnungen';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedDocumentCurrencyEuro;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Client $ownerRecord */
        return (string) $ownerRecord->invoices()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('invoice_date_created', 'desc')
            ->recordUrl(fn (Invoice $record): string => InvoiceResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('invoice_number')->label('Rechnungsnummer')->placeholder('Entwurf')->sortable(),
                TextColumn::make('invoice_type')
                    ->label('Belegart')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceType $state): string => $state->label())
                    ->color(fn (InvoiceType $state): string => $state->color()),
                TextColumn::make('invoice_date_created')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('invoice_date_due')->label('Fällig am')->date('d.m.Y')->sortable(),
                TextColumn::make('invoice_status_id')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->color()),
                TextColumn::make('amount.invoice_total')->label('Gesamt')->money('EUR', locale: 'de'),
                TextColumn::make('amount.invoice_balance')->label('Offen')->money('EUR', locale: 'de'),
            ]);
    }
}
