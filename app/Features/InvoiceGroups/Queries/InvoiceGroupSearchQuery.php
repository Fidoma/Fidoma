<?php

namespace App\Features\InvoiceGroups\Queries;

use App\Features\InvoiceGroups\Models\InvoiceGroup;
use Illuminate\Database\Eloquent\Builder;

final class InvoiceGroupSearchQuery
{
    /** @return Builder<InvoiceGroup> */
    public function query(): Builder
    {
        return InvoiceGroup::query();
    }
}
