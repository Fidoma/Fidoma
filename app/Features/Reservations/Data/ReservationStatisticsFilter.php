<?php

namespace App\Features\Reservations\Data;

use App\Features\Reservations\Enums\ReservationStatus;
use Carbon\CarbonImmutable;

final readonly class ReservationStatisticsFilter
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public ?int $roomId = null,
        public ?ReservationStatus $status = null,
    ) {}

    /** @param array<string, mixed>|null $filters */
    public static function fromArray(?array $filters): self
    {
        $today = CarbonImmutable::today();
        $start = self::date($filters['start_date'] ?? null) ?? $today->startOfMonth();
        $end = self::date($filters['end_date'] ?? null) ?? $today->endOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return new self(
            start: $start->startOfDay(),
            end: $end->addDay()->startOfDay(),
            roomId: filled($filters['room_id'] ?? null) ? (int) $filters['room_id'] : null,
            status: filled($filters['status'] ?? null)
                ? ReservationStatus::tryFrom((int) $filters['status'])
                : null,
        );
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end);
    }

    public function previous(): self
    {
        $days = $this->days();

        return new self(
            start: $this->start->subDays($days),
            end: $this->start,
            roomId: $this->roomId,
            status: $this->status,
        );
    }

    public function previousYear(): self
    {
        return new self(
            start: $this->start->subYear(),
            end: $this->end->subYear(),
            roomId: $this->roomId,
            status: $this->status,
        );
    }

    public function between(CarbonImmutable $start, CarbonImmutable $end): self
    {
        return new self(
            start: $start->startOfDay(),
            end: $end->startOfDay(),
            roomId: $this->roomId,
            status: $this->status,
        );
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
