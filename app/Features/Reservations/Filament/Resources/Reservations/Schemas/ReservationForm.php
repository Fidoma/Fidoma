<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Schemas;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Products\Models\Product;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\ReservationItem;
use App\Features\Reservations\Models\Room;
use App\Features\Reservations\Services\ReservationDefaults;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                Section::make('Reservierung')
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->columnSpanFull()
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
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        TextInput::make('reservation_number')
                            ->label('Reservierungsnummer')
                            ->placeholder('Wird automatisch vergeben')
                            ->maxLength(100),
                        DatePicker::make('reservation_date_created')
                            ->label('Reservierungsdatum')
                            ->default(now()->toDateString())
                            ->required(),
                        DatePicker::make('reservation_date_expires')
                            ->label('Gültig bis')
                            ->default(fn (): string => now()->addDays(app(ReservationDefaults::class)->expirationDays())->toDateString())
                            ->afterOrEqual('reservation_date_created')
                            ->required(),
                        Select::make('reservation_status_id')
                            ->label('Status')
                            ->options(ReservationStatus::options())
                            ->default(ReservationStatus::Open->value)
                            ->required(),
                    ]),
                Section::make('Aufenthalte und Leistungen')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->orderColumn('item_order')
                            ->default(fn (): array => [[
                                'room_id' => request()->integer('room_id') ?: null,
                                'arrival_date' => request()->query('arrival_date'),
                                'departure_date' => request()->query('departure_date'),
                                'item_quantity' => self::nights(request()->query('arrival_date'), request()->query('departure_date')) ?: 1,
                            ]])
                            ->minItems(1)
                            ->reorderable()
                            ->compact()
                            ->addAction(fn (Action $action): Action => $action->label('Aufenthalt hinzufügen')->icon(Heroicon::Plus))
                            ->itemLabel(fn (array $state): string => $state['item_name'] ?? 'Neuer Aufenthalt')
                            ->columns(['default' => 1, 'md' => 2, 'xl' => 12])
                            ->schema([
                                Select::make('room_id')
                                    ->label('Zimmer')
                                    ->options(fn () => Room::query()->orderBy('room_number')->get()
                                        ->mapWithKeys(fn (Room $room): array => [$room->getKey() => $room->displayName()]))
                                    ->searchable()->preload()->required()->columnSpan(['xl' => 3]),
                                DatePicker::make('arrival_date')
                                    ->label('Anreise')->required()->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) => $set('item_quantity', self::nights($state, $get('departure_date')) ?: 1))
                                    ->columnSpan(['xl' => 2]),
                                DatePicker::make('departure_date')
                                    ->label('Abreise')->required()->after('arrival_date')->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) => $set('item_quantity', self::nights($get('arrival_date'), $state) ?: 1))
                                    ->columnSpan(['xl' => 2]),
                                Select::make('product_template')
                                    ->label('Produkt')
                                    ->options(fn () => Product::query()->orderBy('product_name')->pluck('product_name', 'product_id'))
                                    ->searchable()->preload()->live()->dehydrated(false)
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
                                    })
                                    ->columnSpan(['xl' => 5]),
                                Hidden::make('item_product_id'),
                                TextInput::make('item_name')->label('Position')->required()->maxLength(255)->columnSpan(['xl' => 4]),
                                Textarea::make('item_description')->label('Beschreibung')->rows(1)->autosize()->columnSpan(['xl' => 8]),
                                TextInput::make('item_quantity')->label('Menge/Nächte')->numeric()->minValue(0.01)->step(0.01)->required()->columnSpan(['xl' => 2]),
                                Select::make('item_product_unit_id')
                                    ->label('Einheit')->options(fn () => Unit::query()->orderBy('unit_name')->pluck('unit_name', 'unit_id'))
                                    ->searchable()->preload()->columnSpan(['xl' => 2]),
                                TextInput::make('item_price')->label('Einzelpreis')->numeric()->step(0.01)->suffix('€')->required()->columnSpan(['xl' => 2]),
                                TextInput::make('item_discount_amount')->label('Rabatt je Einheit')->numeric()->minValue(0)->step(0.01)->default(0)->suffix('€')->columnSpan(['xl' => 2]),
                                Select::make('item_tax_rate_id')
                                    ->label('Steuersatz')->relationship('taxRate', 'tax_rate_name')->searchable()->preload()->required()->columnSpan(['xl' => 3]),
                                Toggle::make('item_tax_included')->label('Preis inkl. Steuer')->default(false)->columnSpan(['xl' => 1]),
                            ]),
                    ]),
                Section::make('Rabatt und Notizen')
                    ->collapsed()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reservation_discount_percent')->label('Gesamtrabatt in Prozent')->numeric()->minValue(0)->maxValue(100)->default(0)->suffix('%'),
                        TextInput::make('reservation_discount_amount')->label('Gesamtrabatt als Betrag')->numeric()->minValue(0)->default(0)->suffix('€'),
                        Textarea::make('notes')->label('Notizen')->rows(4)->columnSpanFull(),
                    ]),
                Section::make('Reservierungssteuer')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('reservationTaxRates')
                            ->relationship()
                            ->defaultItems(0)
                            ->columns(2)
                            ->schema([
                                Select::make('tax_rate_id')->label('Steuersatz')->options(fn () => TaxRate::query()->pluck('tax_rate_name', 'tax_rate_id'))->required(),
                                Toggle::make('include_item_tax')->label('Positionssteuern einbeziehen'),
                                Hidden::make('reservation_tax_rate_amount')->default(0),
                            ]),
                    ]),
            ]);
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
