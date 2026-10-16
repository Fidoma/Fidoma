<?php

namespace App\Features\Invoices\Filament\Resources\Invoices\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Services\QuoteAmountCalculator;
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
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 12])->components([
            Hidden::make('invoice_type')->default(InvoiceType::Invoice->value),
            Hidden::make('creditinvoice_parent_id'),
            Hidden::make('invoice_discount_percent')->default(0),
            Hidden::make('invoice_discount_amount')->default(0),
            Hidden::make('invoice_terms')->default(fn (): string => app(InvoiceDefaults::class)->terms()),
            Repeater::make('invoiceTaxRates')
                ->relationship()
                ->defaultItems(0)
                ->hidden()
                ->dehydratedWhenHidden()
                ->saveRelationshipsWhenHidden()
                ->schema(self::invoiceTaxRateFields()),
            Callout::make(fn (?Invoice $record): string => $record?->invoice_type === InvoiceType::Credit
                ? 'Gutschrift'
                : 'Stornorechnung')
                ->description(fn (?Invoice $record): string => self::counterpartDescription($record))
                ->icon(fn (?Invoice $record): Heroicon => $record?->invoice_type === InvoiceType::Credit
                    ? Heroicon::OutlinedDocumentMinus
                    : Heroicon::OutlinedNoSymbol)
                ->status(fn (?Invoice $record): string => $record?->invoice_type === InvoiceType::Credit
                    ? 'success'
                    : 'danger')
                ->actions([
                    Action::make('openOriginalInvoice')
                        ->label(fn (?Invoice $record): string => 'Originalrechnung '.($record?->parentInvoice?->invoice_number ?? '').' öffnen')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (?Invoice $record): ?string => $record?->creditinvoice_parent_id
                            ? InvoiceResource::getUrl('edit', ['record' => $record->creditinvoice_parent_id])
                            : null),
                ])
                ->visible(fn (?Invoice $record): bool => $record?->invoice_type !== InvoiceType::Invoice
                    && filled($record?->creditinvoice_parent_id))
                ->columnSpanFull(),
            Section::make(fn (?Invoice $record): string => filled($record?->invoice_number)
                ? 'Rechnung - '.$record->invoice_number
                : 'Rechnung')
                ->afterHeader([
                    Toggle::make('mark_as_sent')
                        ->label('Versendet')
                        ->default(fn (?Invoice $record): bool => $record !== null && $record->invoice_status_id !== InvoiceStatus::Draft)
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function (bool $state, EditInvoice $livewire): void {
                            if ($state) {
                                $livewire->markAsSent();
                            }
                        })
                        ->visible(fn (?Invoice $record): bool => $record !== null && ! $record->is_archived),
                ])
                ->columns(['default' => 1, 'md' => 2, 'xl' => 12])
                ->columnSpan(['default' => 12])
                ->schema([
                    Select::make('client_id')
                        ->label('Kunde')
                        ->relationship('client', 'client_name')
                        ->searchable(['client_name', 'client_email'])
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('client_name')->label('Name')->required()->maxLength(100),
                            TextInput::make('client_email')->label('E-Mail')->email()->maxLength(100),
                            TextInput::make('client_phone')->label('Telefon')->tel()->maxLength(20),
                        ])
                        ->createOptionUsing(fn (array $data): int => app(CreateClient::class)->handle($data)->getKey())
                        ->disabled(fn (Get $get): bool => self::invoiceType($get) !== InvoiceType::Invoice)
                        ->columnSpan(['default' => 1, 'xl' => 4]),
                    TextInput::make('invoice_number')
                        ->label('Rechnungsnummer')
                        ->placeholder('Wird automatisch vergeben')
                        ->maxLength(20)
                        ->columnSpan(['default' => 1, 'xl' => 2]),
                    DatePicker::make('invoice_date_created')
                        ->label('Rechnungsdatum')
                        ->default(now()->toDateString())
                        ->required()
                        ->columnSpan(['default' => 1, 'xl' => 3]),
                    DatePicker::make('invoice_date_due')
                        ->label('Fällig am')
                        ->default(fn (): string => now()->addDays(app(InvoiceDefaults::class)->dueDays())->toDateString())
                        ->afterOrEqual('invoice_date_created')
                        ->required()
                        ->columnSpan(['default' => 1, 'xl' => 3]),
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
                            TextInput::make('item_quantity')->label('Menge')->numeric()->default(1)->step(0.01)->required()->live(),
                            Select::make('item_product_unit_id')->label('Einheit')
                                ->options(fn () => Unit::query()->orderBy('unit_name')->pluck('unit_name', 'unit_id'))
                                ->placeholder('–')
                                ->searchable()->preload(),
                            TextInput::make('item_price')->label('Einzelpreis')->numeric()->step(0.01)->required()->live(),
                            Hidden::make('item_discount_amount')->default(0),
                            Select::make('item_tax_rate_id')->label('Steuersatz')->relationship('taxRate', 'tax_rate_name')
                                ->placeholder('–')
                                ->searchable()->preload()->required()->live(),
                            Hidden::make('item_tax_included')->default(false),
                            Placeholder::make('item_tax_preview')->label('Steuer')
                                ->content(fn (Get $get): string => self::formatAmount(self::calculateItem($get)['tax'])),
                            Placeholder::make('item_total_preview')->label('Summe')
                                ->content(fn (Get $get): string => self::formatAmount(self::calculateItem($get)['total'])),
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
                            Textarea::make('invoice_terms')
                                ->label('Zahlungsbedingungen')
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
                                    'discount_percent' => $get('invoice_discount_percent') ?? 0,
                                    'discount_amount' => $get('invoice_discount_amount') ?? 0,
                                ])
                                ->schema(fn (Get $get): array => [
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
                                        ->minValue(self::invoiceType($get) === InvoiceType::Invoice ? 0 : null)
                                        ->suffix('€'),
                                ])
                                ->action(function (array $data, Set $set): void {
                                    $set('invoice_discount_percent', $data['discount_percent'] ?? 0);
                                    $set('invoice_discount_amount', $data['discount_amount'] ?? 0);
                                }),
                            Action::make('editTaxes')
                                ->label('Steuer')
                                ->icon(Heroicon::OutlinedCalculator)
                                ->color('gray')
                                ->button()
                                ->size(Size::Small)
                                ->modalHeading('Rechnungssteuer')
                                ->modalSubmitActionLabel('Steuer übernehmen')
                                ->fillForm(fn (Get $get): array => [
                                    'tax_rates' => $get('invoiceTaxRates') ?? [],
                                ])
                                ->schema([
                                    Repeater::make('tax_rates')
                                        ->label('Steuersätze')
                                        ->defaultItems(0)
                                        ->columns(2)
                                        ->addActionLabel('Steuer hinzufügen')
                                        ->schema(self::invoiceTaxRateFields()),
                                ])
                                ->action(fn (array $data, Set $set) => $set(
                                    'invoiceTaxRates',
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
    private static function invoiceTaxRateFields(): array
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
            Hidden::make('invoice_tax_rate_amount')->default(0),
        ];
    }

    /** @return array<string, mixed> */
    private static function calculateTotals(Get $get): array
    {
        $items = collect($get('items') ?? []);
        $taxes = collect($get('invoiceTaxRates') ?? []);
        $ids = $items->pluck('item_tax_rate_id')->merge($taxes->pluck('tax_rate_id'))->filter()->unique();
        $rates = TaxRate::query()->whereKey($ids)->pluck('tax_rate_percent', 'tax_rate_id');

        return app(QuoteAmountCalculator::class)->calculate(
            $items->map(fn (array $item): array => [
                'quantity' => $item['item_quantity'] ?? 0, 'price' => $item['item_price'] ?? 0,
                'tax_percent' => ! empty($item['item_tax_rate_id']) ? ($rates[$item['item_tax_rate_id']] ?? 0) : 0,
                'tax_included' => $item['item_tax_included'] ?? false,
                'discount_amount' => $item['item_discount_amount'] ?? 0,
            ])->all(),
            $taxes->map(fn (array $tax): array => [
                'tax_percent' => ! empty($tax['tax_rate_id']) ? ($rates[$tax['tax_rate_id']] ?? 0) : 0,
                'include_item_tax' => $tax['include_item_tax'] ?? false,
            ])->all(),
            (float) ($get('invoice_discount_amount') ?? 0),
            (float) ($get('invoice_discount_percent') ?? 0),
            self::invoiceType($get)->sign(),
        );
    }

    /** @return array{tax: float, total: float} */
    private static function calculateItem(Get $get): array
    {
        $rate = $get('item_tax_rate_id')
            ? (float) (TaxRate::query()->whereKey($get('item_tax_rate_id'))->value('tax_rate_percent') ?? 0)
            : 0;
        $sign = (float) $get('item_quantity') < 0 ? -1 : 1;
        $amounts = app(QuoteAmountCalculator::class)->calculate([[
            'quantity' => $get('item_quantity'), 'price' => $get('item_price'), 'tax_percent' => $rate,
            'tax_included' => $get('item_tax_included'), 'discount_amount' => $get('item_discount_amount'),
        ]], sign: $sign);

        return ['tax' => $amounts['itemTax'], 'total' => $amounts['total']];
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

    private static function invoiceType(Get $get): InvoiceType
    {
        $state = $get('invoice_type');

        return $state instanceof InvoiceType
            ? $state
            : (InvoiceType::tryFrom((string) $state) ?? InvoiceType::Invoice);
    }

    private static function counterpartDescription(?Invoice $invoice): string
    {
        $original = $invoice?->parentInvoice;

        if (! $original) {
            return 'Die Originalrechnung ist nicht mehr verfügbar.';
        }

        $client = $original->client?->client_name ?: '–';
        $date = $original->invoice_date_created?->format('d.m.Y') ?: '–';
        $total = number_format(abs((float) ($original->amount?->invoice_total ?? 0)), 2, ',', '.').' €';

        return "Kunde: {$client} · Rechnungsdatum: {$date} · Betrag: {$total}";
    }
}
