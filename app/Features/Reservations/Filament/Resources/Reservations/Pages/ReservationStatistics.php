<?php

namespace App\Features\Reservations\Filament\Resources\Reservations\Pages;

use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Filament\Resources\Reservations\ReservationResource;
use App\Features\Reservations\Filament\Widgets\ReservationConflicts;
use App\Features\Reservations\Filament\Widgets\ReservationOccupancyChart;
use App\Features\Reservations\Filament\Widgets\ReservationStatisticsKpis;
use App\Features\Reservations\Filament\Widgets\ReservationValueChart;
use App\Features\Reservations\Filament\Widgets\ReservationVolumeChart;
use App\Features\Reservations\Filament\Widgets\RoomStatistics;
use App\Features\Reservations\Models\Room;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\Page;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class ReservationStatistics extends Page
{
    protected static string $resource = ReservationResource::class;

    protected string $view = 'reservations::reservation-statistics';

    protected static ?string $title = 'Statistik & Auswertung';

    /** @var array<string, mixed> */
    public array $filters = [];

    public string $startDate = '';

    public string $endDate = '';

    public string $selectedPeriod = 'month';

    public ?int $roomId = null;

    public ?int $status = null;

    public function mount(): void
    {
        $this->selectPeriod('month');
    }

    public function applyFilters(): void
    {
        $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'roomId' => ['nullable', 'integer', 'exists:ip_rooms,room_id'],
            'status' => ['nullable', 'integer', 'in:'.collect(ReservationStatus::cases())->pluck('value')->implode(',')],
        ], [
            'endDate.after_or_equal' => __('reservations::messages.statistics.validation.end_after_start'),
        ]);

        $this->selectedPeriod = 'custom';
        $this->updateFilters();
    }

    public function selectPeriod(string $period): void
    {
        if (! in_array($period, ['month', 'quarter', 'year'], true)) {
            return;
        }

        $today = CarbonImmutable::today();
        [$start, $end] = match ($period) {
            'quarter' => [$today->startOfQuarter(), $today->endOfQuarter()],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };

        $this->selectedPeriod = $period;
        $this->startDate = $start->toDateString();
        $this->endDate = $end->toDateString();
        $this->resetValidation();
        $this->updateFilters();
    }

    public function updatedRoomId(): void
    {
        $this->updateFilters();
    }

    public function updatedStatus(): void
    {
        $this->updateFilters();
    }

    public function periodLabel(): string
    {
        return __('reservations::messages.statistics.periods.'.$this->selectedPeriod);
    }

    /** @return array<int, string> */
    public function roomOptions(): array
    {
        return Room::query()
            ->orderByRaw('CAST(room_number AS UNSIGNED)')
            ->orderBy('room_number')
            ->get()
            ->mapWithKeys(fn (Room $room): array => [$room->getKey() => $room->displayName()])
            ->all();
    }

    /** @return array<class-string<Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [
            ReservationStatisticsKpis::class,
            ReservationConflicts::class,
            ReservationVolumeChart::class,
            ReservationOccupancyChart::class,
            ReservationValueChart::class,
            RoomStatistics::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 1, 'xl' => 2];
    }

    public function getHeader(): ?View
    {
        return view('reservations::reservation-statistics-header');
    }

    private function updateFilters(): void
    {
        $this->filters = [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'room_id' => $this->roomId,
            'status' => $this->status,
        ];
    }
}
