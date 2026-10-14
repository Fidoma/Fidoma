<?php

namespace App\Features\Invoices\Filament\Resources\Invoices\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
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
use Filament\Support\Icons\Heroicon;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['default' => 12])->components([
            Hidden::make('invoice_type')->default(InvoiceType::Invoice->value),
            Hidden::make('creditinvoice_parent_id'),
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
                ? 'Rechnungsnummer: '.$record->invoice_number
                : 'Rechnungsdetails')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
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
                        ->columnSpan(1),
                    TextInput::make('invoice_number')
                        ->label('Rechnungsnummer')
                        ->placeholder('Wird automatisch vergeben')
                        ->maxLength(20),
                    DatePicker::make('invoice_date_created')
                        ->label('Rechnungsdatum')
                        ->default(now()->toDateString())
                        ->required(),
                    DatePicker::make('invoice_date_due')
                        ->label('Fällig am')
                        ->default(fn (): string => now()->addDays(app(InvoiceDefaults::class)->dueDays())->toDateString())
                        ->afterOrEqual('invoice_date_created')
                        ->required(),
                ]),
            Section::make('Positionen')
                ->columnSpan(['default' => 12])
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('item_order')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->deletable(false)
                        ->reorderable()
                        ->compact()
                        ->addAction(fn (Action $action): Action => $action->label('Neue Rechnungsposition')->icon(Heroicon::Plus))
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
                                }),
                            Hidden::make('item_product_id'),
                            TextInput::make('item_description')->label('Beschreibung'),
                            TextInput::make('item_quantity')->label('Menge')->numeric()->default(1)->step(0.01)->required()->live(),
                            Select::make('item_product_unit_id')->label('Einheit')
                                ->options(fn () => Unit::query()->orderBy('unit_name')->pluck('unit_name', 'unit_id'))
                                ->searchable()->preload(),
                            TextInput::make('item_price')->label('Einzelpreis')->numeric()->step(0.01)->required()->live(),
                            Hidden::make('item_discount_amount')->default(0),
                            Select::make('item_tax_rate_id')->label('Steuersatz')->relationship('taxRate', 'tax_rate_name')
                                ->searchable()->preload()->required()->live(),
                            Hidden::make('item_tax_included')->default(false),
                            Placeholder::make('item_tax_preview')->label('Steuer')
                                ->content(fn (Get $get): string => self::formatAmount(self::calculateItem($get)['tax'])),
                            Placeholder::make('item_total_preview')->label('Summe')
                                ->content(fn (Get $get): string => self::formatAmount(self::calculateItem($get)['total'])),
                            Actions::make([
                                ActionGroup::make([
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
                        ->columnSpanFull(),
                ]),
            Section::make('Rabatt und Zahlungsbedingungen')
                ->collapsed()->columns(['default' => 1, 'md' => 2])->columnSpan(['default' => 12])
                ->schema([
                    TextInput::make('invoice_discount_percent')->label('Gesamtrabatt in Prozent')
                        ->numeric()->minValue(0)->maxValue(100)->default(0)->suffix('%')->live(),
                    TextInput::make('invoice_discount_amount')->label('Gesamtrabatt als Betrag')
                        ->helperText('Ein prozentualer Rabatt hat Vorrang.')->numeric()
                        ->minValue(fn (Get $get): ?int => self::invoiceType($get) === InvoiceType::Invoice ? 0 : null)
                        ->default(0)->suffix('€')->live(),
                    Textarea::make('invoice_terms')->label('Zahlungsbedingungen')
                        ->default(fn (): string => app(InvoiceDefaults::class)->terms())->rows(4)->columnSpanFull(),
                ]),
            Section::make('Rechnungssteuer')
                ->collapsed()->columnSpan(['default' => 12, 'lg' => 6])
                ->schema([
                    Repeater::make('invoiceTaxRates')->relationship()->defaultItems(0)->columns(2)->schema([
                        Select::make('tax_rate_id')->label('Steuersatz')->relationship('taxRate', 'tax_rate_name')
                            ->searchable()->preload()->required()->live(),
                        Toggle::make('include_item_tax')->label('Positionssteuern einbeziehen')->live(),
                        Hidden::make('invoice_tax_rate_amount')->default(0),
                    ]),
                ]),
            Section::make('Summen')
                ->columns(2)->columnSpan(['default' => 12, 'lg' => 6])
                ->schema([
                    Placeholder::make('original_subtotal')->label('Positionssumme')
                        ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['originalSubtotal'])),
                    Placeholder::make('discount')->label('Rabatt')
                        ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['discount'])),
                    Placeholder::make('subtotal')->label('Zwischensumme')
                        ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['subtotal'])),
                    Placeholder::make('item_tax')->label('Steuer Positionen')
                        ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['itemTax'])),
                    Placeholder::make('invoice_tax')->label('Rechnungssteuer')
                        ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['quoteTax'])),
                    Placeholder::make('total')->label('Gesamtsumme')
                        ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['total'])),
                ]),
        ]);
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
