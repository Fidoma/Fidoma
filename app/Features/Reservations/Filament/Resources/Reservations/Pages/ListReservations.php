<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Pages;

use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Filament\Widgets\ReservationListKpis;
use App\Features\Reservations\Models\Reservation;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class ListReservations extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = ReservationResource::class;

    /** @return array<class-string<Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [ReservationListKpis::class];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $counts = Reservation::query()->selectRaw('reservation_status_id, COUNT(*) aggregate')
            ->groupBy('reservation_status_id')->pluck('aggregate', 'reservation_status_id');
        $tabs = ['all' => Tab::make('Alle')->badge((int) $counts->sum())->badgeColor('gray')];

        foreach (ReservationStatus::cases() as $status) {
            $tabs[$status->name] = Tab::make($status->label())
                ->badge((int) ($counts[$status->value] ?? 0))
                ->badgeColor($status->color())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('reservation_status_id', $status->value));
        }

        return $tabs;
    }
}
