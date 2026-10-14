<?php

namespace App\Features\Products\Filament\Resources\Products\Schemas;

use App\Features\TaxRates\Models\TaxRate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('product_sku')
                    ->label('Artikelnummer'),
                TextInput::make('product_name')
                    ->label('Produktname')
                    ->required()
                    ->maxLength(50),
                Textarea::make('product_description')
                    ->label('Beschreibung')
                    ->rows(5)
                    ->columnSpanFull(),
                Select::make('tax_rate_id')
                    ->label('Steuersatz')
                    ->relationship('taxRate', 'tax_rate_name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),
                Select::make('unit_id')
                    ->label('Einheit')
                    ->relationship('unit', 'unit_name')
                    ->searchable()
                    ->preload(),
                TextInput::make('product_price')
                    ->label('Verkaufspreis')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('€')
                    ->live(debounce: 300),
                Toggle::make('product_tax_included')
                    ->label('Preis inklusive Steuer')
                    ->default(false)
                    ->live()
                    ->helperText(fn (Get $get): string => self::priceExample($get))
                    ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                        $price = (float) ($get('product_price') ?? 0);
                        $taxPercent = self::taxPercent($get('tax_rate_id'));

                        if ($price <= 0 || $taxPercent <= 0) {
                            return;
                        }

                        $factor = 1 + ($taxPercent / 100);
                        $convertedPrice = $state ? $price * $factor : $price / $factor;

                        $set('product_price', number_format($convertedPrice, 2, '.', ''));
                    }),
                TextInput::make('purchase_price')
                    ->label('Einkaufspreis')
                    ->numeric()
                    ->step(0.01),
                TextInput::make('provider_name')
                    ->label('Lieferant'),
                Select::make('family_id')
                    ->label('Produktfamilie')
                    ->relationship('family', 'family_name')
                    ->searchable()
                    ->preload(),
                TextInput::make('product_tariff')
                    ->label('Tarifnummer')
                    ->integer(),
            ]);
    }

    private static function priceExample(Get $get): string
    {
        $price = (float) ($get('product_price') ?? 0);
        $taxPercent = self::taxPercent($get('tax_rate_id'));
        $taxIncluded = (bool) $get('product_tax_included');

        if ($price <= 0 || $taxPercent <= 0) {
            return $taxIncluded
                ? 'Der angezeigte Verkaufspreis ist der Bruttopreis.'
                : 'Der angezeigte Verkaufspreis ist der Nettopreis.';
        }

        $factor = 1 + ($taxPercent / 100);
        $netPrice = $taxIncluded ? $price / $factor : $price;
        $grossPrice = $taxIncluded ? $price : $price * $factor;

        return sprintf(
            'Angezeigt: %s · entspricht %s bei %s %% Steuer.',
            self::formatPrice($taxIncluded ? $grossPrice : $netPrice, $taxIncluded ? 'brutto' : 'netto'),
            self::formatPrice($taxIncluded ? $netPrice : $grossPrice, $taxIncluded ? 'netto' : 'brutto'),
            number_format($taxPercent, 2, ',', '.'),
        );
    }

    private static function taxPercent(mixed $taxRateId): float
    {
        if (blank($taxRateId)) {
            return 0;
        }

        return (float) (TaxRate::query()->whereKey($taxRateId)->value('tax_rate_percent') ?? 0);
    }

    private static function formatPrice(float $price, string $type): string
    {
        return number_format($price, 2, ',', '.').' € '.$type;
    }
}
