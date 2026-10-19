<?php

namespace App\Features\Clients\Filament\Resources\Clients\RelationManagers;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Models\Payment;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedBanknotes;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('clients::messages.relations.payments');
    }

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
                TextColumn::make('payment_date')->label(__('clients::messages.fields.payment_date'))->date('d.m.Y')->sortable(),
                TextColumn::make('invoice.invoice_number')->label(__('clients::messages.fields.invoice'))->placeholder('–'),
                TextColumn::make('paymentMethod.payment_method_name')->label(__('clients::messages.fields.payment_method'))->placeholder('–'),
                TextColumn::make('payment_amount')->label(__('clients::messages.fields.amount'))->money('EUR', locale: app()->getLocale())->sortable(),
                TextColumn::make('payment_note')->label(__('clients::messages.fields.note'))->placeholder('–')->wrap(),
            ])
            ->filters([
                SelectFilter::make('payment_method_id')
                    ->label(__('clients::messages.fields.payment_method'))
                    ->options(fn () => PaymentMethod::query()
                        ->orderBy('payment_method_name')
                        ->pluck('payment_method_name', 'payment_method_id'))
                    ->searchable()
                    ->preload(),
            ]);
    }
}
