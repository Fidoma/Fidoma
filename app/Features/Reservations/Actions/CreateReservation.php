<?php

namespace App\Features\Reservations\Actions;

use App\Features\InvoiceGroups\Actions\GenerateInvoiceGroupNumber;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Features\Reservations\Models\Reservation;
use App\Features\Reservations\Services\ReservationDefaults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LogicException;

final class CreateReservation
{
    public function __construct(
        private readonly GenerateInvoiceGroupNumber $generateNumber,
        private readonly ReservationDefaults $defaults,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Reservation
    {
        $userId = Auth::id();
        $invoiceGroupId = $this->defaults->invoiceGroupId();

        if ($userId === null) {
            throw new LogicException('An authenticated user is required to create a reservation.');
        }

        if ($invoiceGroupId === null) {
            throw new LogicException('No number group is configured for reservations.');
        }

        $number = trim((string) ($attributes['reservation_number'] ?? ''));

        return Reservation::query()->create([
            ...$attributes,
            'invoice_id' => 0,
            'user_id' => $userId,
            'invoice_group_id' => $invoiceGroupId,
            'reservation_status_id' => $attributes['reservation_status_id'] ?? ReservationStatus::Open,
            'reservation_number' => $number !== '' ? $number : $this->generateNumber->handle($invoiceGroupId),
            'reservation_url_key' => Str::random(32),
        ]);
    }
}
