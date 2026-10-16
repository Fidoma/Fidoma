<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Models\Quote;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\Quotes\Services\QuoteDefaults;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use App\Filament\Forms\Components\SummaryTableRepeater;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
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
                Hidden::make('quote_discount_percent')->default(0),
                Hidden::make('quote_discount_amount')->default(0),
                Hidden::make('notes')->default(fn (): string => app(QuoteDefaults::class)->notes()),
                Repeater::make('quoteTaxRates')
                    ->relationship()
                    ->defaultItems(0)
                    ->hidden()
                    ->dehydratedWhenHidden()
                    ->saveRelationshipsWhenHidden()
                    ->schema(self::quoteTaxRateFields()),
                Section::make(fn (?Quote $record): string => filled($record?->quote_number)
                    ? 'Angebot - '.$record->quote_number
                    : 'Angebot')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 12,
                    ])
                    ->columnSpan(12)
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
                            ->columnSpan(['default' => 1, 'xl' => 4]),
                        TextInput::make('quote_number')
                            ->label('Angebotsnummer')
                            ->placeholder('Wird automatisch vergeben')
                            ->maxLength(20)
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                        DatePicker::make('quote_date_created')
                            ->label('Angebotsdatum')
                            ->default(now()->toDateString())
                            ->required()
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                        DatePicker::make('quote_date_expires')
                            ->label('Gültig bis')
                            ->default(fn (): string => now()
                                ->addDays(app(QuoteDefaults::class)->expirationDays())
                                ->toDateString())
                            ->required()
                            ->afterOrEqual('quote_date_created')
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                        Select::make('quote_status_id')
                            ->label('Status')
                            ->options(QuoteStatus::options())
                            ->default(QuoteStatus::Draft->value)
                            ->required()
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                    ]),
                Section::make('Positionen')
                    ->columnSpan(['default' => 12])
                    ->schema([
                        SummaryTableRepeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('item_order')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable()
                            ->compact()
                            ->table([
                                TableColumn::make('Position')->markAsRequired()->width('190px'),
                                TableColumn::make('Beschreibung')->width('190px'),
                                TableColumn::make('Menge')->markAsRequired()->width('100px'),
                                TableColumn::make('Einheit')->width('130px'),
                                TableColumn::make('Einzelpreis')->markAsRequired()->width('110px'),
                                TableColumn::make('Steuersatz')->markAsRequired()->width('120px'),
                                TableColumn::make('Steuer')->width('110px'),
                                TableColumn::make('Summe')->width('110px'),
                                TableColumn::make('Aktionen')->hiddenHeaderLabel()->width('56px'),
                            ])
                            ->schema([
                                TextInput::make('item_name')
                                    ->label('Position')
                                    ->datalist(fn (): array => Product::query()
                                        ->orderBy('product_name')
                                        ->pluck('product_name')
                                        ->all())
                                    ->live(debounce: 300)
                                    ->required()
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        $product = Product::query()
                                            ->where('product_name', $state)
                                            ->first();

                                        $set('item_product_id', $product?->getKey());

                                        if (! $product) {
                                            return;
                                        }

                                        $set('item_description', $product->product_description);
                                        $set('item_price', $product->product_price);
                                        $set('item_tax_rate_id', $product->tax_rate_id);
                                        $set('item_product_unit_id', $product->unit_id);
                                        $set('item_tax_included', $product->product_tax_included);
                                    }),
                                Hidden::make('item_product_id'),
                                TextInput::make('item_description')->label('Beschreibung'),
                                TextInput::make('item_quantity')
                                    ->label('Menge')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->step(0.01)
                                    ->live(),
                                Select::make('item_product_unit_id')
                                    ->label('Einheit')
                                    ->options(fn () => Unit::query()->orderBy('unit_name')->pluck('unit_name', 'unit_id'))
                                    ->placeholder('–')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('item_price')
                                    ->label('Einzelpreis')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->live(),
                                Hidden::make('item_discount_amount')->default(0),
                                Select::make('item_tax_rate_id')
                                    ->label('Steuersatz')
                                    ->relationship('taxRate', 'tax_rate_name')
                                    ->placeholder('–')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Hidden::make('item_tax_included')->default(false),
                                Placeholder::make('item_tax_preview')
                                    ->label('Steuer')
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateItemAmounts($get)['tax'])),
                                Placeholder::make('item_total_preview')
                                    ->label('Summe')
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateItemAmounts($get)['total'])),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('duplicateItem')
                                            ->label('Position duplizieren')
                                            ->icon(Heroicon::OutlinedSquare2Stack)
                                            ->color('gray')
                                            ->action(fn (Component $component) => self::duplicateItem($component)),
                                        Action::make('addItem')
                                            ->label('Zeile hinzufügen')
                                            ->icon(Heroicon::Plus)
                                            ->color('gray')
                                            ->action(fn (Component $component) => self::addItem($component)),
                                        Action::make('toggleTaxIncluded')
                                            ->label('Preis inklusive Steuer')
                                            ->icon(fn (Get $get): Heroicon => $get('item_tax_included')
                                                ? Heroicon::CheckCircle
                                                : Heroicon::OutlinedCheckCircle)
                                            ->color(fn (Get $get): string => $get('item_tax_included') ? 'success' : 'gray')
                                            ->action(fn (Get $get, Set $set) => $set(
                                                'item_tax_included',
                                                ! $get('item_tax_included'),
                                                shouldCallUpdatedHooks: true,
                                            )),
                                        Action::make('deleteItem')
                                            ->label('Position löschen')
                                            ->icon(Heroicon::OutlinedTrash)
                                            ->color('danger')
                                            ->action(fn (Component $component) => self::deleteItem($component)),
                                    ])
                                        ->label('Optionen')
                                        ->icon(Heroicon::OutlinedEllipsisVertical)
                                        ->tooltip('Optionen')
                                        ->iconButton(),
                                ])->alignCenter(),
                            ])
                            ->summaryLayout(tableColumnCount: 9, leadingColumnSpan: 5)
                            ->summaryActionsInTable()
                            ->summaryLeadingComponent(
                                Textarea::make('notes')
                                    ->label('Angebotsbedingungen')
                                    ->hiddenLabel()
                                    ->extraAttributes([
                                        'class' => 'ring-1! ring-gray-950/10! shadow-sm! dark:ring-white/20!',
                                    ])
                                    ->rows(4),
                            )
                            ->summaryActions([
                                Action::make('editDiscount')
                                    ->label('Rabatt')
                                    ->icon(Heroicon::OutlinedReceiptPercent)
                                    ->color('gray')
                                    ->button()
                                    ->size(Size::Small)
                                    ->modalHeading('Rabatt')
                                    ->modalSubmitActionLabel('Rabatt übernehmen')
                                    ->fillForm(fn (Get $get): array => [
                                        'discount_percent' => $get('quote_discount_percent') ?? 0,
                                        'discount_amount' => $get('quote_discount_amount') ?? 0,
                                    ])
                                    ->schema([
                                        TextInput::make('discount_percent')
                                            ->label('Gesamtrabatt in Prozent')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%'),
                                        TextInput::make('discount_amount')
                                            ->label('Gesamtrabatt als Betrag')
                                            ->helperText('Ein prozentualer Rabatt hat Vorrang.')
                                            ->numeric()
                                            ->minValue(0)
                                            ->suffix('€'),
                                    ])
                                    ->action(function (array $data, Set $set): void {
                                        $set('quote_discount_percent', $data['discount_percent'] ?? 0);
                                        $set('quote_discount_amount', $data['discount_amount'] ?? 0);
                                    }),
                                Action::make('editTaxes')
                                    ->label('Steuer')
                                    ->icon(Heroicon::OutlinedCalculator)
                                    ->color('gray')
                                    ->button()
                                    ->size(Size::Small)
                                    ->modalHeading('Angebotssteuer')
                                    ->modalSubmitActionLabel('Steuer übernehmen')
                                    ->fillForm(fn (Get $get): array => [
                                        'tax_rates' => $get('quoteTaxRates') ?? [],
                                    ])
                                    ->schema([
                                        Repeater::make('tax_rates')
                                            ->label('Steuersätze')
                                            ->defaultItems(0)
                                            ->columns(2)
                                            ->addActionLabel('Steuer hinzufügen')
                                            ->schema(self::quoteTaxRateFields()),
                                    ])
                                    ->action(fn (array $data, Set $set) => $set(
                                        'quoteTaxRates',
                                        $data['tax_rates'] ?? [],
                                        shouldCallUpdatedHooks: true,
                                    )),
                            ])
                            ->summaryRows(function (Get $get): array {
                                $totals = self::calculateTotals($get);
                                $taxRows = collect($totals['taxBreakdown'])
                                    ->mapWithKeys(fn (float $amount, int|string $taxRate): array => [
                                        'Steuer '.self::formatTaxRate((float) $taxRate).' %' => self::formatAmount($amount),
                                    ])
                                    ->all();

                                return [
                                    'Zwischensumme' => self::formatAmount($totals['subtotal']),
                                    ...$taxRows,
                                    'Steuer gesamt' => self::formatAmount($totals['itemTax'] + $totals['quoteTax']),
                                    'Gesamtsumme' => self::formatAmount($totals['total']),
                                ];
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int, Component> */
    private static function quoteTaxRateFields(): array
    {
        return [
            Select::make('tax_rate_id')
                ->label('Steuersatz')
                ->options(fn (): array => TaxRate::query()
                    ->orderBy('tax_rate_name')
                    ->pluck('tax_rate_name', 'tax_rate_id')
                    ->all())
                ->searchable()
                ->preload()
                ->required(),
            Toggle::make('include_item_tax')->label('Positionssteuern einbeziehen'),
            Hidden::make('quote_tax_rate_amount')->default(0),
        ];
    }

    /** @return array<string, mixed> */
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

    private static function formatTaxRate(float $taxRate): string
    {
        return rtrim(rtrim(number_format($taxRate, 2, ',', ''), '0'), ',');
    }

    private static function deleteItem(Component $component): void
    {
        $repeater = $component->getParentRepeater();
        $itemKey = $component->getParentRepeaterItem()?->getStatePath(isAbsolute: false);

        if ((! $repeater) || blank($itemKey)) {
            return;
        }

        $items = $repeater->getRawState() ?? [];

        if (! array_key_exists($itemKey, $items)) {
            return;
        }

        unset($items[$itemKey]);

        $repeater->rawState($items);
        $repeater->callAfterStateUpdated();
    }

    private static function duplicateItem(Component $component): void
    {
        $repeater = $component->getParentRepeater();
        $itemKey = $component->getParentRepeaterItem()?->getStatePath(isAbsolute: false);

        if ((! $repeater) || blank($itemKey)) {
            return;
        }

        $items = $repeater->getRawState() ?? [];

        if (! array_key_exists($itemKey, $items)) {
            return;
        }

        $newKey = $repeater->generateUuid();

        if ($newKey) {
            $items[$newKey] = $items[$itemKey];
        } else {
            $items[] = $items[$itemKey];
        }

        $repeater->rawState($items);
        $repeater->callAfterStateUpdated();
    }

    private static function addItem(Component $component): void
    {
        $repeater = $component->getParentRepeater();
        $itemKey = $component->getParentRepeaterItem()?->getStatePath(isAbsolute: false);

        if ((! $repeater) || blank($itemKey)) {
            return;
        }

        $items = $repeater->getRawState() ?? [];

        if (! array_key_exists($itemKey, $items)) {
            return;
        }

        $newKey = $repeater->generateUuid();

        if (! $newKey) {
            $items[] = [];
            $newKey = array_key_last($items);
            unset($items[$newKey]);
        }

        $itemsWithNewRow = [];

        foreach ($items as $key => $item) {
            $itemsWithNewRow[$key] = $item;

            if ((string) $key === (string) $itemKey) {
                $itemsWithNewRow[$newKey] = [];
            }
        }

        $repeater->rawState($itemsWithNewRow);
        $repeater->getChildSchema($newKey)->fill();
        $repeater->callAfterStateUpdated();
    }
}
