<?php

namespace App\Features\Clients\Filament\Resources\Clients\RelationManagers;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Payments\Models\Payment;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Zahlungen';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedBanknotes;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Client $ownerRecord */
        return (string) $ownerRecord->payments()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('payment_date', 'desc')
            ->recordUrl(fn (Payment $record): string => InvoiceResource::getUrl('edit', ['record' => $record->invoice_id]))
            ->columns([
                TextColumn::make('payment_date')->label('Zahlungsdatum')->date('d.m.Y')->sortable(),
                TextColumn::make('invoice.invoice_number')->label('Rechnung')->placeholder('–'),
                TextColumn::make('paymentMethod.payment_method_name')->label('Zahlungsart')->placeholder('–'),
                TextColumn::make('payment_amount')->label('Betrag')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('payment_note')->label('Notiz')->placeholder('–')->wrap(),
            ]);
    }
}
