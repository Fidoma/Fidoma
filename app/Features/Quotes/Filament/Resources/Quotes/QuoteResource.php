<?php

namespace App\Features\Quotes\Filament\Resources\Quotes;

use App\Features\Quotes\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Features\Quotes\Filament\Resources\Quotes\Pages\EditQuote;
use App\Features\Quotes\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Features\Quotes\Filament\Resources\Quotes\Schemas\QuoteForm;
use App\Features\Quotes\Filament\Resources\Quotes\Tables\QuotesTable;
use App\Features\Quotes\Models\Quote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $modelLabel = 'Angebot';

    protected static ?string $pluralModelLabel = 'Angebote';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'quote_number';

    public static function form(Schema $schema): Schema
    {
        return QuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }
}
