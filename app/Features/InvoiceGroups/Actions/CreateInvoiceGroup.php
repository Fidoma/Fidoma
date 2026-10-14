<?php

namespace App\Features\InvoiceGroups\Actions;

use App\Features\InvoiceGroups\Models\InvoiceGroup;

final class CreateInvoiceGroup
{
    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): InvoiceGroup
    {
        return InvoiceGroup::query()->create($attributes);
    }
}
