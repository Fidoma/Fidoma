<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Models\QuoteItem;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\Quotes\Services\QuoteDefaults;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class QuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 12,
            ])
            ->components([
                Section::make('Angebotskopf')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->columnSpan(['default' => 12])
                    ->schema([
                        Select::make('client_id')
                            ->label('Kunde')
                            ->relationship('client', 'client_name')
                            ->searchable(['client_name', 'client_email'])
                            ->preload()
                            ->default(fn (): ?int => request()->integer('client_id') ?: null)
                            ->required()
                            ->createOptionForm([
                                TextInput::make('client_name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('client_email')
                                    ->label('E-Mail')
                                    ->email()
                                    ->maxLength(100),
                                TextInput::make('client_phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->maxLength(20),
                            ])
                            ->createOptionUsing(fn (array $data): int => app(CreateClient::class)->handle($data)->getKey())
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),
                        TextInput::make('quote_number')
                            ->label('Angebotsnummer')
                            ->placeholder('Wird automatisch vergeben')
                            ->maxLength(20),
                        DatePicker::make('quote_date_created')
                            ->label('Angebotsdatum')
                            ->default(now()->toDateString())
                            ->required(),
                        DatePicker::make('quote_date_expires')
                            ->label('Gültig bis')
                            ->default(fn (): string => now()
                                ->addDays(app(QuoteDefaults::class)->expirationDays())
                                ->toDateString())
                            ->required()
                            ->afterOrEqual('quote_date_created'),
                        Select::make('quote_status_id')
                            ->label('Status')
                            ->options(QuoteStatus::options())
                            ->default(QuoteStatus::Draft->value)
                            ->required(),
                    ]),
                Section::make('Positionen')
                    ->columnSpan(['default' => 12])
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->orderColumn('item_order')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable()
                            ->compact()
                            ->addAction(fn (Action $action): Action => $action
                                ->label('Neue Produktzeile')
                                ->icon(Heroicon::Plus))
                            ->itemLabel(fn (array $state): string => $state['item_name'] ?? 'Neue Position')
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'xl' => 12,
                            ])
                            ->schema([
                                Select::make('product_template')
                                    ->label('Produkt')
                                    ->options(fn () => Product::query()->orderBy('product_name')->pluck('product_name', 'product_id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Select $component, ?QuoteItem $record): void {
                                        $component->state($record?->item_product_id);
                                    })
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        $product = Product::query()->find($state);

                                        if (! $product) {
                                            return;
                                        }

                                        $set('item_name', $product->product_name);
                                        $set('item_description', $product->product_description);
                                        $set('item_price', $product->product_price);
                                        $set('item_tax_rate_id', $product->tax_rate_id);
                                        $set('item_product_id', $product->getKey());
                                        $set('item_product_unit_id', $product->unit_id);
                                    })
                                    ->columnSpan([
                                        'default' => 1,
                                        'sm' => 2,
                                        'xl' => 3,
                                    ]),
                                Hidden::make('item_product_id'),
                                TextInput::make('item_name')
                                    ->label('Position')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan([
                                        'default' => 1,
                                        'sm' => 2,
                                        'xl' => 3,
                                    ]),
                                Textarea::make('item_description')
                                    ->label('Beschreibung')
                                    ->required()
                                    ->rows(1)
                                    ->autosize()
                                    ->columnSpan([
                                        'default' => 1,
                                        'sm' => 2,
                                        'xl' => 6,
                                    ]),
                                TextInput::make('item_quantity')
                                    ->label('Menge')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->step(0.01)
                                    ->live()
                                    ->columnSpan(['xl' => 2]),
                                Select::make('item_product_unit_id')
                                    ->label('Einheit')
                                    ->options(fn () => Unit::query()
                                        ->orderBy('unit_name')
                                        ->get()
                                        ->mapWithKeys(fn (Unit $unit): array => [
                                            $unit->getKey() => $unit->unit_name_plrl
                                                ? "{$unit->unit_name} / {$unit->unit_name_plrl}"
                                                : $unit->unit_name,
                                        ]))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(['xl' => 2]),
                                TextInput::make('item_price')
                                    ->label('Einzelpreis')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('€')
                                    ->live()
                                    ->columnSpan(['xl' => 2]),
                                TextInput::make('item_discount_amount')
                                    ->label('Rabatt je Einheit')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->step(0.01)
                                    ->suffix('€')
                                    ->live()
                                    ->columnSpan(['xl' => 2]),
                                Select::make('item_tax_rate_id')
                                    ->label('Steuersatz')
                                    ->relationship('taxRate', 'tax_rate_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->columnSpan(['xl' => 3]),
                                Toggle::make('item_tax_included')
                                    ->label('Preis inkl. Steuer')
                                    ->default(false)
                                    ->live()
                                    ->columnSpan(['xl' => 1]),
                                Placeholder::make('item_tax_preview')
                                    ->label('Steuer')
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateItemAmounts($get)['tax']))
                                    ->columnSpan(['xl' => 6]),
                                Placeholder::make('item_total_preview')
                                    ->label('Summe')
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateItemAmounts($get)['total']))
                                    ->columnSpan(['xl' => 6]),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Rabatt und Notizen')
                    ->collapsed()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpan(['default' => 12])
                    ->schema([
                        TextInput::make('quote_discount_percent')
                            ->label('Gesamtrabatt in Prozent')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->suffix('%')
                            ->live(),
                        TextInput::make('quote_discount_amount')
                            ->label('Gesamtrabatt als Betrag')
                            ->helperText('Wenn ein prozentualer Rabatt gesetzt ist, hat dieser Vorrang.')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('€')
                            ->live(),
                        Textarea::make('notes')
                            ->label('Angebotsnotizen')
                            ->default(fn (): string => app(QuoteDefaults::class)->notes())
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Angebotssteuer')
                    ->collapsed()
                    ->columnSpan(['default' => 12, 'lg' => 6])
                    ->schema([
                        Repeater::make('quoteTaxRates')
                            ->relationship()
                            ->defaultItems(0)
                            ->schema([
                                Select::make('tax_rate_id')
                                    ->label('Steuersatz')
                                    ->relationship('taxRate', 'tax_rate_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Toggle::make('include_item_tax')
                                    ->label('Positionssteuern einbeziehen')
                                    ->live(),
                                Hidden::make('quote_tax_rate_amount')
                                    ->default(0),
                            ])
                            ->columns(2),
                    ]),
                Section::make('Summen')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('calculated_original_subtotal')
                            ->label('Positionssumme')
                            ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['originalSubtotal'])),
                        Placeholder::make('calculated_discount')
                            ->label('Rabatt')
                            ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['discount'])),
                        Placeholder::make('calculated_subtotal')
                            ->label('Zwischensumme')
                            ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['subtotal'])),
                        Placeholder::make('calculated_item_tax')
                            ->label('Steuer Positionen')
                            ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['itemTax'])),
                        Placeholder::make('calculated_quote_tax')
                            ->label('Angebotssteuer')
                            ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['quoteTax'])),
                        Placeholder::make('calculated_total')
                            ->label('Gesamtsumme')
                            ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['total'])),
                    ])
                    ->columnSpan(['default' => 12, 'lg' => 6]),
            ]);
    }

    /**
     * @return array{originalSubtotal: float, discount: float, subtotal: float, itemTax: float, quoteTax: float, total: float}
     */
    private static function calculateTotals(Get $get): array
    {
        $items = collect($get('items') ?? []);
        $quoteTaxes = collect($get('quoteTaxRates') ?? []);
        $taxRateIds = $items->pluck('item_tax_rate_id')
            ->merge($quoteTaxes->pluck('tax_rate_id'))
            ->filter()
            ->unique();
        $taxRates = TaxRate::query()
            ->whereKey($taxRateIds)
            ->pluck('tax_rate_percent', 'tax_rate_id');

        return app(QuoteAmountCalculator::class)->calculate(
            $items->map(fn (array $item): array => [
                'quantity' => $item['item_quantity'] ?? 0,
                'price' => $item['item_price'] ?? 0,
                'tax_percent' => ! empty($item['item_tax_rate_id'])
                    ? ($taxRates[$item['item_tax_rate_id']] ?? 0)
                    : 0,
                'tax_included' => $item['item_tax_included'] ?? false,
                'discount_amount' => $item['item_discount_amount'] ?? 0,
            ])->all(),
            $quoteTaxes->map(fn (array $quoteTax): array => [
                'tax_percent' => ! empty($quoteTax['tax_rate_id'])
                    ? ($taxRates[$quoteTax['tax_rate_id']] ?? 0)
                    : 0,
                'include_item_tax' => $quoteTax['include_item_tax'] ?? false,
            ])->all(),
            (float) ($get('quote_discount_amount') ?? 0),
            (float) ($get('quote_discount_percent') ?? 0),
        );
    }

    /**
     * @return array{tax: float, total: float}
     */
    private static function calculateItemAmounts(Get $get): array
    {
        $taxRateId = $get('item_tax_rate_id');
        $taxPercent = $taxRateId
            ? (float) (TaxRate::query()->whereKey($taxRateId)->value('tax_rate_percent') ?? 0)
            : 0;
        $amounts = app(QuoteAmountCalculator::class)->calculate([[
            'quantity' => $get('item_quantity'),
            'price' => $get('item_price'),
            'tax_percent' => $taxPercent,
            'tax_included' => $get('item_tax_included'),
            'discount_amount' => $get('item_discount_amount'),
        ]]);

        return [
            'tax' => $amounts['itemTax'],
            'total' => $amounts['total'],
        ];
    }

    private static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' €';
    }
}
