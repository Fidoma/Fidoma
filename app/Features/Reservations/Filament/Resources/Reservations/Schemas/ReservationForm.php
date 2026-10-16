<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\Schemas\Components\ReservationItemsRepeater;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\ReservationItem;
use App\Features\Reservations\Models\Room;
use App\Features\TaxRates\Models\TaxRate;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                Section::make(fn (?Reservation $record): string => filled($record?->reservation_number)
                    ? 'Reservierung - '.$record->reservation_number
                    : 'Reservierung')
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
                    ->columnSpan(12)
                    ->schema([
                        ReservationItemsRepeater::make('items')
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
                            ->addable()
                            ->addAction(fn (Action $action): Action => $action->label('Zeile hinzufügen'))
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
                                Select::make('product_template')
                                    ->label('Position')
                                    ->options(fn () => Product::query()->orderBy('product_name')->pluck('product_name', 'product_id'))
                                    ->native()
                                    ->live()
                                    ->required()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(fn (Select $component, ?ReservationItem $record) => $component->state($record?->item_product_id))
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        $product = Product::query()->find($state);
                                        if (! $product) {
                                            return;
                                        }
                                        $set('item_product_id', $product->getKey());
                                        $set('item_name', $product->product_name);
                                        $set('item_description', $product->product_description);
                                        $set('item_price', $product->product_price);
                                        $set('item_tax_rate_id', $product->tax_rate_id);
                                        $set('item_product_unit_id', $product->unit_id);
                                        $set('item_tax_included', $product->product_tax_included);
                                    }),
                                Hidden::make('item_product_id'),
                                Hidden::make('item_name')->required()->validationAttribute('Position'),
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
                                    'Steuer gesamt' => self::formatAmount($totals['itemTax']),
                                    'Gesamtsumme' => self::formatAmount($totals['total']),
                                ];
                            }),
                    ]),
            ]);
    }

    /** @return array<string, mixed> */
    private static function calculateTotals(Get $get): array
    {
        $items = collect($get('items') ?? []);
        $taxRateIds = $items->pluck('item_tax_rate_id')->filter()->unique();
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
