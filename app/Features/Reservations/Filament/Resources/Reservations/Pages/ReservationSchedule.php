<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Pages;

use App\Features\Clients\Models\Client;
use App\Features\Products\Models\Product;
use App\Features\Reservations\Actions\CreateReservation as CreateReservationAction;
use App\Features\Reservations\Actions\RecalculateReservation;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Models\ReservationItem;
use App\Features\Reservations\Models\Room;
use App\Filament\Concerns\WithoutPageHeading;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ReservationSchedule extends Page
{
    use WithoutPageHeading;

    protected static string $resource = ReservationResource::class;

    protected string $view = 'filament.resources.reservations.pages.reservation-schedule';

    protected static ?string $title = 'Belegungsplan';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Reservierung')
            ->icon(Heroicon::Plus)
            ->modalHeading('Reservierung hinzufügen')
            ->modalSubmitActionLabel('Reservierung erstellen')
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments): array => [
                'arrival_date' => $arguments['arrival_date'] ?? now()->toDateString(),
                'departure_date' => $arguments['departure_date'] ?? now()->addDay()->toDateString(),
                'room_id' => $arguments['room_id'] ?? null,
            ])
            ->schema([
                Select::make('client_id')
                    ->label('Kunde')
                    ->options(fn (): array => Client::query()
                        ->where('client_active', true)
                        ->orderBy('client_name')
                        ->get()
                        ->mapWithKeys(fn (Client $client): array => [
                            $client->getKey() => $client->client_name ?: $client->client_company,
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_id')
                    ->label('Produkt')
                    ->options(fn (): array => Product::query()
                        ->orderBy('product_name')
                        ->pluck('product_name', 'product_id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('arrival_date')
                    ->label('Anreisedatum')
                    ->default(now()->toDateString())
                    ->required(),
                DatePicker::make('departure_date')
                    ->label('Abreisedatum')
                    ->default(now()->addDay()->toDateString())
                    ->after('arrival_date')
                    ->required(),
                Select::make('room_id')
                    ->label('Zimmer')
                    ->options(fn (): array => Room::query()
                        ->where('room_active', true)
                        ->orderBy('room_number')
                        ->get()
                        ->mapWithKeys(fn (Room $room): array => [$room->getKey() => $room->displayName()])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data, Action $action): void {
                $reservation = DB::transaction(function () use ($data): Reservation {
                    $product = Product::query()->findOrFail($data['product_id']);
                    $arrival = CarbonImmutable::parse($data['arrival_date']);
                    $departure = CarbonImmutable::parse($data['departure_date']);
                    $reservation = app(CreateReservationAction::class)->handle([
                        'client_id' => $data['client_id'],
                        'reservation_date_created' => now()->toDateString(),
                        'reservation_status_id' => ReservationStatus::Open,
                        'reservation_discount_amount' => 0,
                        'reservation_discount_percent' => 0,
                    ]);

                    $reservation->items()->create([
                        'room_id' => $data['room_id'],
                        'arrival_date' => $arrival->toDateString(),
                        'departure_date' => $departure->toDateString(),
                        'item_tax_rate_id' => $product->tax_rate_id,
                        'item_tax_included' => $product->product_tax_included,
                        'item_product_id' => $product->getKey(),
                        'item_name' => $product->product_name,
                        'item_description' => $product->product_description,
                        'item_quantity' => max(1, $arrival->diffInDays($departure)),
                        'item_price' => $product->product_price,
                        'item_discount_amount' => 0,
                        'item_order' => 1,
                        'item_product_unit_id' => $product->unit_id,
                    ]);

                    return app(RecalculateReservation::class)->handle($reservation);
                });

                Notification::make()->success()->title('Reservierung wurde erstellt.')->send();
                $action->redirect(ReservationResource::getUrl('edit', ['record' => $reservation]));
            });
    }

    /** @return array{startDate: string, days: int, resources: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>} */
    public function schedule(): array
    {
        $defaultStart = CarbonImmutable::today()->subDays(32);
        $start = $this->dateFromQuery('start') ?? $defaultStart;
        $end = $this->dateFromQuery('end') ?? $start->addDays(90);

        if (! $end->isAfter($start) || $start->diffInDays($end) > 366) {
            $start = $defaultStart;
            $end = $start->addDays(90);
        }

        $resources = Room::query()->orderBy('room_number')->get()
            ->map(fn (Room $room): array => ['id' => 'room-'.$room->getKey(), 'name' => $room->displayName()])
            ->values()->all();

        $events = ReservationItem::query()
            ->with(['room', 'reservation.client', 'reservation.invoice'])
            ->whereDate('arrival_date', '<', $end->toDateString())
            ->whereDate('departure_date', '>', $start->toDateString())
            ->whereHas('room')
            ->get()
            ->map(function (ReservationItem $item): array {
                $reservation = $item->reservation;
                $status = $reservation->reservation_status_id;
                $invoiceStatus = $reservation->invoice?->invoice_status_id?->value ?? 0;
                $billing = match ($invoiceStatus) {
                    1 => ['label' => 'Entwurf', 'class' => 'open'],
                    2 => ['label' => 'Offen', 'class' => 'open'],
                    4 => ['label' => 'Bezahlt', 'class' => 'paid'],
                    default => ['label' => 'Nicht berechnet', 'class' => 'open'],
                };
                $colors = match ($status) {
                    ReservationStatus::Confirmed => ['background' => '#f1f3f5', 'border' => '#22c55e', 'font' => '#14532d'],
                    ReservationStatus::Canceled => ['background' => '#f1f3f5', 'border' => '#ef4444', 'font' => '#7f1d1d'],
                    default => ['background' => '#f1f3f5', 'border' => '#cbd5e1', 'font' => '#334155'],
                };

                return [
                    'id' => 'reservation-item-'.$item->getKey(),
                    'reservationId' => $reservation->getKey(),
                    'resource' => 'room-'.$item->room_id,
                    'start' => $item->arrival_date->format('Y-m-d').'T14:00:00',
                    'end' => $item->departure_date->format('Y-m-d').'T11:00:00',
                    'text' => $reservation->client?->client_name ?: $reservation->client?->client_company ?: 'Unbekannter Gast',
                    'status' => $status->label(),
                    'billingStatus' => $billing['label'],
                    'billingClass' => $billing['class'],
                    'invoiceNumber' => $reservation->invoice?->invoice_number,
                    'backColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'fontColor' => $colors['font'],
                    'editUrl' => ReservationResource::getUrl('edit', ['record' => $reservation]),
                ];
            })->values()->all();

        return [
            'startDate' => $start->toDateString(),
            'days' => (int) $start->diffInDays($end),
            'resources' => $resources,
            'events' => $events,
        ];
    }

    private function dateFromQuery(string $key): ?CarbonImmutable
    {
        $value = request()->query($key);

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date?->format('Y-m-d') === $value ? $date : null;
    }
}
