<?php

namespace App\Features\Clients\Filament\Resources\Clients\RelationManagers;

use App\Features\Clients\Models\Client;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Features\Quotes\Models\Quote;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedDocumentText;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('clients::messages.relations.quotes');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Client $ownerRecord */
        return (string) $ownerRecord->quotes()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('quote_date_created', 'desc')
            ->recordUrl(fn (Quote $record): string => QuoteResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('quote_number')->label(__('clients::messages.fields.quote_number'))->placeholder(__('clients::messages.placeholders.draft'))->sortable(),
                TextColumn::make('quote_date_created')->label(__('clients::messages.fields.date'))->date('d.m.Y')->sortable(),
                TextColumn::make('quote_date_expires')->label(__('clients::messages.fields.valid_until'))->date('d.m.Y')->sortable(),
                TextColumn::make('quote_status_id')
                    ->label(__('clients::messages.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (QuoteStatus $state): string => __('clients::messages.quote_statuses.'.strtolower($state->name)))
                    ->color(fn (QuoteStatus $state): string => $state->color()),
                TextColumn::make('amount.quote_item_subtotal')->label(__('clients::messages.fields.net'))->money('EUR', locale: app()->getLocale()),
                TextColumn::make('amount.quote_total')->label(__('clients::messages.fields.total'))->money('EUR', locale: app()->getLocale()),
            ])
            ->filters([
                SelectFilter::make('quote_status_id')
                    ->label(__('clients::messages.fields.status'))
                    ->options(collect(QuoteStatus::cases())
                        ->mapWithKeys(fn (QuoteStatus $status): array => [$status->value => __('clients::messages.quote_statuses.'.strtolower($status->name))])),
            ]);
    }
}
