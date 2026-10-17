<?php

namespace App\Features\Contracts\Services;

use App\Features\InvoiceGroups\Models\InvoiceGroup;

final class ContractDefaults
{
    public function invoiceGroupId(): ?int
    {
        return InvoiceGroup::query()
            ->where(function ($query): void {
                $query
                    ->where('invoice_group_name', 'like', '%Vertrag%')
                    ->orWhere('invoice_group_name', 'like', '%Verträg%');
            })
            ->orderBy('invoice_group_id')
            ->value('invoice_group_id');
    }
}
