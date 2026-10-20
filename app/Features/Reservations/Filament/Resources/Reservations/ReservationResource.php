<?php

namespace App\Features\Reservations\Filament\Resources\Reservations;

use App\Features\Reservations\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\EditReservation;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\ListReservations;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\ReservationSchedule;
use App\Features\Reservations\Filament\Resources\Reservations\Schemas\ReservationForm;
use App\Features\Reservations\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Features\Reservations\Models\Reservation;
use App\Features\TradeFairs\Filament\Pages\TradeFairs;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    use HasConfigurableNavigation;

    protected static ?string $model = Reservation::class;

    protected static function navigationModule(): string
    {
        return 'reservations';
    }

    protected static ?string $modelLabel = 'Reservierung';

    protected static ?string $pluralModelLabel = 'Reservierungen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Reservierungen';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('reservations::messages.feature');
    }

    public static function getModelLabel(): string
    {
        return __('reservations::messages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reservations::messages.feature');
    }

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
            NavigationItem::make(fn (): string => __('trade-fairs::messages.upcoming'))
                ->key(TradeFairs::class)
                ->group(static::getNavigationGroup())
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->sort(3)
                ->url(fn (): string => TradeFairs::getUrl())
                ->isActiveWhen(fn (): bool => request()->routeIs(TradeFairs::getRouteName())),
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
