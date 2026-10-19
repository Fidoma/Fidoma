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

    protected static ?string $title = 'Angebote';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedDocumentText;

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
                TextColumn::make('quote_number')->label('Angebotsnummer')->placeholder('Entwurf')->sortable(),
                TextColumn::make('quote_date_created')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('quote_date_expires')->label('Gültig bis')->date('d.m.Y')->sortable(),
                TextColumn::make('quote_status_id')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (QuoteStatus $state): string => $state->label())
                    ->color(fn (QuoteStatus $state): string => $state->color()),
                TextColumn::make('amount.quote_item_subtotal')->label('Netto')->money('EUR', locale: 'de'),
                TextColumn::make('amount.quote_total')->label('Gesamt')->money('EUR', locale: 'de'),
            ])
            ->filters([
                SelectFilter::make('quote_status_id')
                    ->label('Status')
                    ->options(QuoteStatus::options()),
            ]);
    }
}
