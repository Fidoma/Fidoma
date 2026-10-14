<?php

namespace App\Features\Invoices\Queries;

use App\Features\Invoices\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;

final class InvoiceSearchQuery
{
    /** @return Builder<Invoice> */
    public function query(?string $search = null): Builder
    {
        return Invoice::query()->with(['client', 'amount'])->when($search, fn (Builder $query, string $search) => $query
            ->where(fn (Builder $query) => $query->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $query) => $query->where('client_name', 'like', "%{$search}%"))));
    }
}
