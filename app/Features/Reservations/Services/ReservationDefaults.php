<?php

namespace App\Features\Reservations\Services;

use App\Features\InvoiceGroups\Models\InvoiceGroup;
use Illuminate\Support\Facades\DB;

final class ReservationDefaults
{
    public function invoiceGroupId(): ?int
    {
        $configuredId = (int) $this->value('default_reservation_group', 0);

        if ($configuredId && InvoiceGroup::query()->whereKey($configuredId)->exists()) {
            return $configuredId;
        }

        return InvoiceGroup::query()
            ->where('invoice_group_name', 'like', '%Reservierung%')
            ->value('invoice_group_id');
    }

    public function expirationDays(): int
    {
        return max(0, (int) $this->value('reservations_expire_after', 30));
    }

    private function value(string $key, mixed $default): mixed
    {
        return DB::table('ip_settings')->where('setting_key', $key)->value('setting_value') ?? $default;
    }
}
