<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\Reservations\Actions\ConvertReservationToInvoice;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\CreateReservation as CreateReservationPage;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\EditReservation;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\Room;
use App\Features\TaxRates\Models\TaxRate;
use Carbon\CarbonImmutable;
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

class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 12])
            ->components([
                Hidden::make('reservation_discount_percent')->default(0),
                Hidden::make('reservation_discount_amount')->default(0),
                Hidden::make('notes')->default(''),
                Repeater::make('reservationTaxRates')
                    ->relationship()
                    ->defaultItems(0)
                    ->hidden()
                    ->dehydratedWhenHidden()
                    ->saveRelationshipsWhenHidden()
                    ->schema(self::reservationTaxRateFields()),
                Section::make(fn (?Reservation $record): string => filled($record?->reservation_number)
                    ? 'Reservierung - '.$record->reservation_number
                    : 'Reservierung')
                    ->afterHeader([
                        Actions::make([
                            ActionGroup::make([
                                Action::make('createReservation')
                                    ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
                                    ->icon(Heroicon::OutlinedCheck)
                                    ->action(function (CreateReservationPage $livewire): void {
                                        $livewire->create();
                                    })
                                    ->visible(fn ($livewire): bool => $livewire instanceof CreateReservationPage),
                                Action::make('createAnotherReservation')
                                    ->label(__('filament-panels::resources/pages/create-record.form.actions.create_another.label'))
                                    ->icon(Heroicon::OutlinedPlus)
                                    ->action(function (CreateReservationPage $livewire): void {
                                        $livewire->createAnother();
                                    })
                                    ->visible(fn ($livewire): bool => $livewire instanceof CreateReservationPage),
                                Action::make('convertReservationToInvoice')
                                    ->label(fn (?Reservation $record): string => $record?->invoice_id
                                        ? 'Rechnung öffnen'
                                        : 'In Rechnung umwandeln')
                                    ->icon(Heroicon::OutlinedDocumentText)
                                    ->visible(fn ($livewire): bool => $livewire instanceof EditReservation)
                                    ->requiresConfirmation(fn (?Reservation $record): bool => ! (bool) $record?->invoice_id)
                                    ->action(function (Action $action, Reservation $record): void {
                                        $invoice = app(ConvertReservationToInvoice::class)->handle($record);
                                        Notification::make()->success()->title('Rechnungsentwurf ist verfügbar.')->send();
                                        $action->redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                                    }),
                            ])
                                ->label('Aktionen')
                                ->icon(Heroicon::OutlinedEllipsisVertical)
                                ->iconButton()
                                ->tooltip('Aktionen'),
                            Action::make('saveReservation')
                                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                                ->icon(Heroicon::OutlinedCheck)
                                ->iconButton()
                                ->tooltip(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                                ->action(function (EditReservation $livewire): void {
                                    $livewire->save();
                                })
                                ->visible(fn ($livewire): bool => $livewire instanceof EditReservation),
                        ]),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 12])
                    ->columnSpan(12)
                    ->schema([
                        Select::make('client_id')
                            ->label('Kunde')
                            ->relationship('client', 'client_name')
                            ->searchable(['client_name', 'client_company', 'client_email'])
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('client_name')->label('Name')->required()->maxLength(100),
                                TextInput::make('client_email')->label('E-Mail')->email()->maxLength(100),
                                TextInput::make('client_phone')->label('Telefon')->tel()->maxLength(30),
                            ])
                            ->createOptionUsing(fn (array $data): int => app(CreateClient::class)->handle($data)->getKey())
                            ->columnSpan(['default' => 1, 'xl' => 4]),
                        TextInput::make('reservation_number')
                            ->label('Reservierungsnummer')
                            ->placeholder('Wird automatisch vergeben')
                            ->maxLength(100)
                            ->columnSpan(['default' => 1, 'xl' => 2]),
                        DatePicker::make('reservation_date_created')
                            ->label('Reservierungsdatum')
                            ->default(now()->toDateString())
                            ->required()
                            ->columnSpan(['default' => 1, 'xl' => 3]),
                        Select::make('reservation_status_id')
                            ->label('Status')
                            ->options(ReservationStatus::options())
                            ->default(ReservationStatus::Open->value)
                            ->required()
                            ->columnSpan(['default' => 1, 'xl' => 3]),
                    ]),
                Section::make('Positionen')
                    ->columnSpan(['default' => 12])
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->relationship()
                            ->orderColumn('item_order')
                            ->default(fn (): array => [[
                                'room_id' => request()->integer('room_id') ?: null,
                                'arrival_date' => request()->query('arrival_date'),
                                'departure_date' => request()->query('departure_date'),
                                'item_quantity' => self::nights(request()->query('arrival_date'), request()->query('departure_date')) ?: 1,
                            ]])
                            ->minItems(1)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable()
                            ->compact()
                            ->table([
                                TableColumn::make('Position')->markAsRequired()->width('170px'),
                                TableColumn::make('Beschreibung')->width('190px'),
                                TableColumn::make('Anreise')->markAsRequired()->width('135px'),
                                TableColumn::make('Abreise')->markAsRequired()->width('135px'),
                                TableColumn::make('Menge')->markAsRequired()->width('110px'),
                                TableColumn::make('Zimmer')->markAsRequired()->width('160px'),
                                TableColumn::make('Einzelpreis')->markAsRequired()->width('110px'),
                                TableColumn::make('Steuersatz')->markAsRequired()->width('110px'),
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
                                DatePicker::make('arrival_date')
                                    ->label('Anreise')->required()->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) => $set('item_quantity', self::nights($state, $get('departure_date')) ?: 1)),
                                DatePicker::make('departure_date')
                                    ->label('Abreise')->required()->after('arrival_date')->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) => $set('item_quantity', self::nights($get('arrival_date'), $state) ?: 1)),
                                TextInput::make('item_quantity')->label('Menge')->numeric()->minValue(0.01)->step(0.01)->required()->live(),
                                Select::make('room_id')
                                    ->label('Zimmer')
                                    ->options(fn () => Room::query()->orderBy('room_number')->get()
                                        ->mapWithKeys(fn (Room $room): array => [$room->getKey() => $room->displayName()]))
                                    ->native()
                                    ->required(),
                                Hidden::make('item_product_unit_id'),
                                TextInput::make('item_price')->label('Einzelpreis')->numeric()->step(0.01)->required()->live(),
                                Hidden::make('item_discount_amount')->default(0),
                                Select::make('item_tax_rate_id')
                                    ->label('Steuersatz')->relationship('taxRate', 'tax_rate_name')->native()->required()->live(),
                                Placeholder::make('item_total_preview')
                                    ->label('Summe')
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateItem($get)['total'])),
                                Hidden::make('item_tax_included')->default(false),
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
                            ->columnSpanFull(),
                        Actions::make([
                            Action::make('editTerms')
                                ->label('Reservierungsbedingungen')
                                ->icon(Heroicon::OutlinedDocumentText)
                                ->color('gray')
                                ->iconButton()
                                ->tooltip('Reservierungsbedingungen')
                                ->modalHeading('Reservierungsbedingungen')
                                ->fillForm(fn (Get $get): array => ['notes' => $get('notes') ?? ''])
                                ->schema([
                                    Textarea::make('notes')
                                        ->label('Reservierungsbedingungen')
                                        ->placeholder('Zahlungsbedingungen ...')
                                        ->rows(6),
                                ])
                                ->action(fn (array $data, Set $set) => $set('notes', $data['notes'] ?? '')),
                            Action::make('editDiscount')
                                ->label('Rabatt')
                                ->icon(Heroicon::OutlinedReceiptPercent)
                                ->color('gray')
                                ->iconButton()
                                ->tooltip('Rabatt')
                                ->modalHeading('Rabatt')
                                ->modalSubmitActionLabel('Rabatt übernehmen')
                                ->fillForm(fn (Get $get): array => [
                                    'discount_percent' => $get('reservation_discount_percent') ?? 0,
                                    'discount_amount' => $get('reservation_discount_amount') ?? 0,
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
                                    $set('reservation_discount_percent', $data['discount_percent'] ?? 0);
                                    $set('reservation_discount_amount', $data['discount_amount'] ?? 0);
                                }),
                            Action::make('editTaxes')
                                ->label('Steuer')
                                ->icon(Heroicon::OutlinedCalculator)
                                ->color('gray')
                                ->iconButton()
                                ->tooltip('Steuer')
                                ->modalHeading('Reservierungssteuer')
                                ->modalSubmitActionLabel('Steuer übernehmen')
                                ->fillForm(fn (Get $get): array => [
                                    'tax_rates' => $get('reservationTaxRates') ?? [],
                                ])
                                ->schema([
                                    Repeater::make('tax_rates')
                                        ->label('Steuersätze')
                                        ->defaultItems(0)
                                        ->columns([
                                            'default' => 1,
                                            'md' => 2,
                                        ])
                                        ->addActionLabel('Steuer hinzufügen')
                                        ->schema(self::reservationTaxRateFields()),
                                ])
                                ->action(fn (array $data, Set $set) => $set(
                                    'reservationTaxRates',
                                    $data['tax_rates'] ?? [],
                                    shouldCallUpdatedHooks: true,
                                )),
                        ])->columnSpanFull(),
                        Section::make('Gesamtsumme')
                            ->columns(2)
                            ->schema([
                                Placeholder::make('subtotal_preview')
                                    ->label('Zwischensumme')
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['subtotal'])),
                                Placeholder::make('tax_total_preview')
                                    ->label('Steuer gesamt')
                                    ->content(fn (Get $get): string => self::formatAmount(
                                        self::calculateTotals($get)['itemTax'] + self::calculateTotals($get)['quoteTax'],
                                    )),
                                Placeholder::make('grand_total_preview')
                                    ->label('Gesamtsumme')
                                    ->content(fn (Get $get): string => self::formatAmount(self::calculateTotals($get)['total']))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int, Component> */
    private static function reservationTaxRateFields(): array
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
            Hidden::make('reservation_tax_rate_amount')->default(0),
        ];
    }

    /** @return array<string, mixed> */
    private static function calculateTotals(Get $get): array
    {
        $items = collect($get('items') ?? []);
        $reservationTaxes = collect($get('reservationTaxRates') ?? []);
        $taxRateIds = $items->pluck('item_tax_rate_id')
            ->merge($reservationTaxes->pluck('tax_rate_id'))
            ->filter()
            ->unique();
        $taxRates = TaxRate::query()->whereKey($taxRateIds)->pluck('tax_rate_percent', 'tax_rate_id');

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
            $reservationTaxes->map(fn (array $reservationTax): array => [
                'tax_percent' => ! empty($reservationTax['tax_rate_id'])
                    ? ($taxRates[$reservationTax['tax_rate_id']] ?? 0)
                    : 0,
                'include_item_tax' => $reservationTax['include_item_tax'] ?? false,
            ])->all(),
            (float) ($get('reservation_discount_amount') ?? 0),
            (float) ($get('reservation_discount_percent') ?? 0),
        );
    }

    /** @return array{tax: float, total: float} */
    private static function calculateItem(Get $get): array
    {
        $taxPercent = $get('item_tax_rate_id')
            ? (float) (TaxRate::query()->whereKey($get('item_tax_rate_id'))->value('tax_rate_percent') ?? 0)
            : 0;
        $amounts = app(QuoteAmountCalculator::class)->calculate([[
            'quantity' => $get('item_quantity'),
            'price' => $get('item_price'),
            'tax_percent' => $taxPercent,
            'tax_included' => $get('item_tax_included'),
            'discount_amount' => $get('item_discount_amount'),
        ]]);

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

    private static function nights(mixed $arrival, mixed $departure): ?int
    {
        if (! $arrival || ! $departure) {
            return null;
        }

        try {
            $start = CarbonImmutable::parse($arrival)->startOfDay();
            $end = CarbonImmutable::parse($departure)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $end->isAfter($start) ? (int) $start->diffInDays($end) : null;
    }
}
