<?php

namespace App\Features\Clients\Services;

use App\Features\InvoiceGroups\Models\InvoiceGroup;

final class ClientDefaults
{
    public function invoiceGroupId(): ?int
    {
        return InvoiceGroup::query()
            ->where('invoice_group_name', 'like', '%Kund%')
            ->orderBy('invoice_group_id')
            ->value('invoice_group_id');
    }
}
