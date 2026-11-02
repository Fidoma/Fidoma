<?php

namespace App\Features\Reservations\Filament\Actions;

use App\Features\Clients\Models\Client;
use App\Features\Products\Models\Product;
use App\Features\Reservations\Actions\CreateReservationWithItem;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Models\Room;
use App\Features\Reservations\Services\LastSelectedReservationProduct;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

final class CreateReservationModalAction
{
    public static function make(string $name = 'create', bool $redirectAfterCreate = true): Action
    {
        return Action::make($name)
            ->label('Reservierung')
            ->icon(Heroicon::Plus)
            ->modalHeading('Reservierung hinzufügen')
            ->modalSubmitActionLabel('Reservierung erstellen')
            ->modalWidth(Width::Large)
            ->fillForm(function (array $arguments): array {
                $draft = is_array($arguments['draft'] ?? null) ? $arguments['draft'] : [];

                return [
                    'client_id' => $draft['client_id'] ?? null,
                    'product_id' => $draft['product_id'] ?? app(LastSelectedReservationProduct::class)->currentId(),
                    'arrival_date' => $draft['arrival_date'] ?? $arguments['arrival_date'] ?? now()->toDateString(),
                    'departure_date' => $draft['departure_date'] ?? $arguments['departure_date'] ?? now()->addDay()->toDateString(),
                    'room_id' => $draft['room_id'] ?? $arguments['room_id'] ?? null,
                ];
            })
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
                    ->live()
                    ->afterStateUpdated(fn (mixed $state) => app(LastSelectedReservationProduct::class)->remember($state))
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
            ->action(function (array $data, Action $action) use ($redirectAfterCreate): void {
                $reservation = app(CreateReservationWithItem::class)->handle($data);

                Notification::make()->success()->title('Reservierung wurde erstellt.')->send();

                if ($redirectAfterCreate) {
                    $action->redirect(ReservationResource::getUrl('edit', ['record' => $reservation]));
                }
            });
    }
}
