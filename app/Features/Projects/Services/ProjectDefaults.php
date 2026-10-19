<?php

namespace App\Features\Projects\Services;

use App\Features\InvoiceGroups\Models\InvoiceGroup;

final class ProjectDefaults
{
    public function invoiceGroupId(): ?int
    {
        return InvoiceGroup::query()
            ->where('invoice_group_name', 'like', '%Projekt%')
            ->orderBy('invoice_group_id')
            ->value('invoice_group_id');
    }
}
