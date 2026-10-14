<?php

namespace App\Features\Invoices\Services;

use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Invoices\Enums\InvoiceType;
use Illuminate\Support\Facades\DB;

final class InvoiceDefaults
{
    public function invoiceGroupId(): ?int
    {
        $configuredId = (int) $this->value('default_invoice_group', 0);

        if ($configuredId && InvoiceGroup::query()->whereKey($configuredId)->exists()) {
            return $configuredId;
        }

        return InvoiceGroup::query()->where('invoice_group_name', 'like', '%Rechnung%')->value('invoice_group_id');
    }

    public function invoiceGroupIdFor(InvoiceType $type): ?int
    {
        if ($type === InvoiceType::Invoice) {
            return $this->invoiceGroupId();
        }

        return InvoiceGroup::query()
            ->where('invoice_group_name', $type->numberGroupName())
            ->value('invoice_group_id');
    }

    public function dueDays(): int
    {
        return max(0, (int) $this->value('invoices_due_after', 30));
    }

    public function terms(): string
    {
        return (string) $this->value('default_invoice_terms', '');
    }

    public function paymentMethodId(): int
    {
        return max(0, (int) $this->value('invoice_default_payment_method', 0));
    }

    private function value(string $key, mixed $default): mixed
    {
        return DB::table('ip_settings')->where('setting_key', $key)->value('setting_value') ?? $default;
    }
}
