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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedDocumentCurrencyEuro;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('clients::messages.relations.invoices');
    }

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
                TextColumn::make('invoice_number')->label(__('clients::messages.fields.invoice_number'))->placeholder(__('clients::messages.placeholders.draft'))->sortable(),
                TextColumn::make('invoice_type')
                    ->label(__('clients::messages.fields.document_type'))
                    ->badge()
                    ->formatStateUsing(fn (InvoiceType $state): string => __('clients::messages.invoice_types.'.$state->value))
                    ->color(fn (InvoiceType $state): string => $state->color()),
                TextColumn::make('invoice_date_created')->label(__('clients::messages.fields.date'))->date('d.m.Y')->sortable(),
                TextColumn::make('invoice_date_due')->label(__('clients::messages.fields.due_date'))->date('d.m.Y')->sortable(),
                TextColumn::make('invoice_status_id')
                    ->label(__('clients::messages.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => __('clients::messages.invoice_statuses.'.strtolower($state->name)))
                    ->color(fn (InvoiceStatus $state): string => $state->color()),
                TextColumn::make('amount.invoice_total')->label(__('clients::messages.fields.total'))->money('EUR', locale: app()->getLocale()),
                TextColumn::make('amount.invoice_balance')->label(__('clients::messages.fields.outstanding'))->money('EUR', locale: app()->getLocale()),
            ])
            ->filters([
                SelectFilter::make('invoice_status_id')
                    ->label(__('clients::messages.fields.status'))
                    ->options(collect(InvoiceStatus::cases())
                        ->mapWithKeys(fn (InvoiceStatus $status): array => [$status->value => __('clients::messages.invoice_statuses.'.strtolower($status->name))])),
                SelectFilter::make('invoice_type')
                    ->label(__('clients::messages.fields.document_type'))
                    ->options(collect(InvoiceType::cases())
                        ->mapWithKeys(fn (InvoiceType $type): array => [$type->value => __('clients::messages.invoice_types.'.$type->value)])),
            ]);
    }
}
