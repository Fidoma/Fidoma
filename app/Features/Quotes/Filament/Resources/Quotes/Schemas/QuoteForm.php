<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Actions\ArchiveQuote;
use App\Features\Quotes\Actions\ConvertQuoteToInvoice;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Filament\Actions\QuoteEmailAction;
use App\Features\Quotes\Filament\Resources\Quotes\Pages\CreateQuote as CreateQuotePage;
use App\Features\Quotes\Filament\Resources\Quotes\Pages\EditQuote;
use App\Features\Quotes\Models\Quote;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\Quotes\Services\QuoteDefaults;
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
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
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
                    ? __('quotes::messages.singular').' - '.$record->quote_number
                    : __('quotes::messages.singular'))
                    ->afterHeader([
                        Actions::make([
                            ActionGroup::make([
                                Action::make('createQuote')
                                    ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
                                    ->icon(Heroicon::OutlinedCheck)
                                    ->action(function (CreateQuotePage $livewire): void {
                                        $livewire->create();
                                    })
                                    ->visible(fn ($livewire): bool => $livewire instanceof CreateQuotePage),
                                Action::make('createAnotherQuote')
                                    ->label(__('filament-panels::resources/pages/create-record.form.actions.create_another.label'))
                                    ->icon(Heroicon::OutlinedPlus)
                                    ->action(function (CreateQuotePage $livewire): void {
                                        $livewire->createAnother();
                                    })
                                    ->visible(fn ($livewire): bool => $livewire instanceof CreateQuotePage),
                                Action::make('convertQuoteToInvoice')
                                    ->label(fn (?Quote $record): string => $record?->invoice_id
                                        ? __('quotes::messages.actions.open_invoice')
                                        : __('quotes::messages.actions.convert_invoice'))
                                    ->icon(Heroicon::OutlinedDocumentText)
                                    ->visible(fn ($livewire): bool => $livewire instanceof EditQuote)
                                    ->disabled(fn (?Quote $record): bool => $record === null || $record->is_archived)
                                    ->requiresConfirmation(fn (?Quote $record): bool => ! (bool) $record?->invoice_id)
                                    ->action(function (Action $action, Quote $record): void {
                                        $invoice = app(ConvertQuoteToInvoice::class)->handle($record);

                                        Notification::make()->success()->title(__('quotes::messages.conversion.success'))->send();
                                        $action->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                                    }),
                                QuoteEmailAction::make()->visible(fn (?Quote $record): bool => $record !== null),
                                Action::make('archiveQuote')
                                    ->label(__('quotes::messages.actions.archive'))
                                    ->icon(Heroicon::OutlinedArchiveBox)
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->visible(fn ($livewire): bool => $livewire instanceof EditQuote)
                                    ->disabled(fn (?Quote $record): bool => $record === null || $record->is_archived)
                                    ->action(function (Quote $record): void {
                                        app(ArchiveQuote::class)->handle($record);
                                        $record->refresh();
                                        Notification::make()->success()->title(__('quotes::messages.archive.success'))->send();
                                    }),
                            ])
                                ->label(__('quotes::messages.actions.options'))
                                ->icon(Heroicon::OutlinedEllipsisVertical)
                                ->iconButton()
                                ->tooltip(__('quotes::messages.actions.options')),
                            Action::make('saveQuote')
                                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                                ->icon(Heroicon::OutlinedCheck)
                                ->iconButton()
                                ->tooltip(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                                ->action(function (EditQuote $livewire): void {
                                    $livewire->save();
                                })
                                ->visible(fn ($livewire, ?Quote $record): bool => $livewire instanceof EditQuote
                                    && ! (bool) $record?->is_archived),
                        ]),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 12,
                    ])
                    ->columnSpan(['default' => 12])
                    ->schema([
                        Select::make('client_id')
                            ->label(__('quotes::messages.fields.client'))
                            ->relationship('client', 'client_name')
                            ->searchable(['client_name', 'client_email'])
                            ->preload()
                            ->default(fn (): ?int => request()->integer('client_id') ?: null)
                            ->required()
                            ->createOptionForm([
                                TextInput::make('client_name')
                                    ->label(__('quotes::messages.fields.name'))
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('client_email')
                                    ->label(__('quotes::messages.fields.email'))
                                    ->email()
                                    ->maxLength(100),
                                TextInput::make('client_phone')
                                    ->label(__('quotes::messages.fields.phone'))
                                    ->tel()
                                    ->maxLength(20),
                            ])
                            ->createOptionUsing(fn (array $data): int => app(CreateClient::class)->handle($data)->getKey())
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                        TextInput::make('quote_number')
                            ->label(__('quotes::messages.fields.quote_number'))
                            ->placeholder(__('quotes::messages.placeholders.automatic_number'))
                            ->maxLength(20)
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                        DatePicker::make('quote_date_created')
                            ->label(__('quotes::messages.fields.quote_date'))
                            ->default(now()->toDateString())
                            ->required()
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                        DatePicker::make('quote_date_expires')
                            ->label(__('quotes::messages.fields.valid_until'))
                            ->default(fn (): string => now()
                                ->addDays(app(QuoteDefaults::class)->expirationDays())
                                ->toDateString())
                            ->required()
                            ->afterOrEqual('quote_date_created')
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                        Select::make('quote_status_id')
                            ->label(__('quotes::messages.fields.status'))
                            ->options(QuoteStatus::options())
                            ->default(QuoteStatus::Draft->value)
                            ->required()
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                    ]),
                Section::make(__('quotes::messages.sections.items'))
                    ->columnSpan(['default' => 12])
                    ->schema([
                        Repeater::make('items')
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
                                TableColumn::make(__('quotes::messages.fields.position'))->markAsRequired()->width('190px'),
                                TableColumn::make(__('quotes::messages.fields.description'))->width('190px'),
                                TableColumn::make(__('quotes::messages.fields.quantity'))->markAsRequired()->width('100px'),
                                TableColumn::make(__('quotes::messages.fields.unit'))->width('130px'),
                                TableColumn::make(__('quotes::messages.fields.unit_price'))->markAsRequired()->width('110px'),
                                TableColumn::make(__('quotes::messages.fields.tax_rate'))->markAsRequired()->width('120px'),
                                TableColumn::make(__('quotes::messages.fields.tax'))->width('110px'),
                                TableColumn::make(__('quotes::messages.fields.sum'))->width('110px'),
                                TableColumn::make(__('quotes::messages.fields.actions'))->hiddenHeaderLabel()->width('56px'),
                            ])
                            ->schema([
                                TextInput::make('item_name')
                                    ->label(__('quotes::messages.fields.position'))
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
                                TextInput::make('item_description')->label(__('quotes::messages.fields.description')),
                                TextInput::make('item_quantity')
                                    ->label(__('quotes::messages.fields.quantity'))
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->step(0.01)
                                    ->live(),
                                Select::make('item_product_unit_id')
                                    ->label(__('quotes::messages.fields.unit'))
                                    ->options(fn () => Unit::query()->orderBy('unit_name')->pluck('unit_name', 'unit_id'))
                                    ->placeholder('–')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('item_price')
                                    ->label(__('quotes::messages.fields.unit_price'))
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->live(),
                                Hidden::make('item_discount_amount')->default(0),
                                Select::make('item_tax_rate_id')
                                    ->label(__('quotes::messages.fields.tax_rate'))
                                    ->relationship('taxRate', 'tax_rate_name')
                                    ->placeholder('–')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Hidden::make('item_tax_included')->default(false),
                                Placeholder::make('item_tax_preview')
                                    ->label(__('quotes::messages.fields.tax'))
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateItemAmounts($get)['tax'])),
                                Placeholder::make('item_total_preview')
                                    ->label(__('quotes::messages.fields.sum'))
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateItemAmounts($get)['total'])),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('duplicateItem')
                                            ->label(__('quotes::messages.actions.duplicate_item'))
                                            ->icon(Heroicon::OutlinedSquare2Stack)
                                            ->color('gray')
                                            ->action(fn (Component $component) => self::duplicateItem($component)),
                                        Action::make('addItem')
                                            ->label(__('quotes::messages.actions.add_item'))
                                            ->icon(Heroicon::Plus)
                                            ->color('gray')
                                            ->action(fn (Component $component) => self::addItem($component)),
                                        Action::make('toggleTaxIncluded')
                                            ->label(__('quotes::messages.actions.tax_included'))
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
                                            ->label(__('quotes::messages.actions.delete_item'))
                                            ->icon(Heroicon::OutlinedTrash)
                                            ->color('danger')
                                            ->action(fn (Component $component) => self::deleteItem($component)),
                                    ])
                                        ->label(__('quotes::messages.actions.options'))
                                        ->icon(Heroicon::OutlinedEllipsisVertical)
                                        ->tooltip(__('quotes::messages.actions.options'))
                                        ->iconButton(),
                                ])->alignCenter(),
                            ])
                            ->columnSpanFull(),
                        Actions::make([
                            Action::make('editTerms')
                                ->label(__('quotes::messages.fields.terms'))
                                ->icon(Heroicon::OutlinedDocumentText)
                                ->color('gray')
                                ->iconButton()
                                ->tooltip(__('quotes::messages.fields.terms'))
                                ->modalHeading(__('quotes::messages.fields.terms'))
                                ->fillForm(fn (Get $get): array => ['notes' => $get('notes') ?? ''])
                                ->schema([
                                    Textarea::make('notes')
                                        ->label(__('quotes::messages.fields.terms'))
                                        ->placeholder(__('quotes::messages.placeholders.terms'))
                                        ->rows(6),
                                ])
                                ->action(fn (array $data, Set $set) => $set('notes', $data['notes'] ?? '')),
                            Action::make('editDiscount')
                                ->label(__('quotes::messages.fields.discount'))
                                ->icon(Heroicon::OutlinedReceiptPercent)
                                ->color('gray')
                                ->iconButton()
                                ->tooltip(__('quotes::messages.fields.discount'))
                                ->modalHeading(__('quotes::messages.fields.discount'))
                                ->modalSubmitActionLabel(__('quotes::messages.actions.apply_discount'))
                                ->fillForm(fn (Get $get): array => [
                                    'discount_percent' => $get('quote_discount_percent') ?? 0,
                                    'discount_amount' => $get('quote_discount_amount') ?? 0,
                                ])
                                ->schema([
                                    TextInput::make('discount_percent')
                                        ->label(__('quotes::messages.fields.discount_percent'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->suffix('%'),
                                    TextInput::make('discount_amount')
                                        ->label(__('quotes::messages.fields.discount_amount'))
                                        ->helperText(__('quotes::messages.hints.percentage_discount_priority'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->suffix('€'),
                                ])
                                ->action(function (array $data, Set $set): void {
                                    $set('quote_discount_percent', $data['discount_percent'] ?? 0);
                                    $set('quote_discount_amount', $data['discount_amount'] ?? 0);
                                }),
                            Action::make('editTaxes')
                                ->label(__('quotes::messages.fields.tax'))
                                ->icon(Heroicon::OutlinedCalculator)
                                ->color('gray')
                                ->iconButton()
                                ->tooltip(__('quotes::messages.fields.tax'))
                                ->modalHeading(__('quotes::messages.fields.tax'))
                                ->modalSubmitActionLabel(__('quotes::messages.actions.apply_tax'))
                                ->fillForm(fn (Get $get): array => [
                                    'tax_rates' => $get('quoteTaxRates') ?? [],
                                ])
                                ->schema([
                                    Repeater::make('tax_rates')
                                        ->label(__('quotes::messages.fields.tax_rates'))
                                        ->defaultItems(0)
                                        ->columns([
                                            'default' => 1,
                                            'md' => 2,
                                        ])
                                        ->addActionLabel(__('quotes::messages.actions.add_tax'))
                                        ->schema(self::quoteTaxRateFields()),
                                ])
                                ->action(fn (array $data, Set $set) => $set(
                                    'quoteTaxRates',
                                    $data['tax_rates'] ?? [],
                                    shouldCallUpdatedHooks: true,
                                )),
                        ])->columnSpanFull(),
                        Section::make(__('quotes::messages.summary.grand_total'))
                            ->columns(2)
                            ->schema([
                                Placeholder::make('subtotal_preview')
                                    ->label(__('quotes::messages.summary.subtotal'))
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['subtotal'])),
                                Placeholder::make('tax_total_preview')
                                    ->label(__('quotes::messages.summary.tax_total'))
                                    ->content(fn (Get $get): string => self::formatAmount(
                                        self::calculateTotals($get)['itemTax'] + self::calculateTotals($get)['quoteTax'],
                                    )),
                                Placeholder::make('grand_total_preview')
                                    ->label(__('quotes::messages.summary.grand_total'))
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['total']))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int, Component> */
    private static function quoteTaxRateFields(): array
    {
        return [
            Select::make('tax_rate_id')
                ->label(__('quotes::messages.fields.tax_rate'))
                ->options(fn (): array => TaxRate::query()
                    ->orderBy('tax_rate_name')
                    ->pluck('tax_rate_name', 'tax_rate_id')
                    ->all())
                ->searchable()
                ->preload()
                ->required(),
            Toggle::make('include_item_tax')->label(__('quotes::messages.fields.include_item_tax')),
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
