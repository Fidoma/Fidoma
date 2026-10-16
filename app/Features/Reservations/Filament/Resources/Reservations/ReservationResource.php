<?php

namespace App\Features\Reservations\Filament\Resources\Reservations;

use App\Features\Reservations\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\EditReservation;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\ListReservations;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\ReservationSchedule;
use App\Features\Reservations\Filament\Resources\Reservations\Schemas\ReservationForm;
use App\Features\Reservations\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Features\Reservations\Models\Reservation;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static ?string $modelLabel = 'Reservierung';

    protected static ?string $pluralModelLabel = 'Reservierungen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Reservierungen';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reservation_number';

    public static function form(Schema $schema): Schema
    {
        return ReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    /** @return array<NavigationItem> */
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if ($items === []) {
            return [];
        }

        $routeBaseName = static::getRouteBaseName();
        $items[0]->isActiveWhen(fn (): bool => request()->routeIs([
            $routeBaseName.'.index',
            $routeBaseName.'.create',
            $routeBaseName.'.edit',
        ]));

        return [
            ...$items,
            NavigationItem::make('Belegungsplan')
                ->key(static::class.'::schedule')
                ->group(static::getNavigationGroup())
                ->icon(Heroicon::OutlinedCalendarDateRange)
                ->sort(2)
                ->url(static::getUrl('schedule'))
                ->isActiveWhen(fn (): bool => request()->routeIs($routeBaseName.'.schedule')),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
            'schedule' => ReservationSchedule::route('/belegungsplan'),
            'create' => CreateReservation::route('/create'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }
}
