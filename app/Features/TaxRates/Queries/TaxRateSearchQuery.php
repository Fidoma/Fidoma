<?php

namespace App\Features\TaxRates\Queries;

use App\Features\TaxRates\Models\TaxRate;
use Illuminate\Database\Eloquent\Builder;

final class TaxRateSearchQuery
{
    /**
     * @return Builder<TaxRate>
     */
    public function query(?string $search = null): Builder
    {
        return TaxRate::query()
            ->when($search, fn (Builder $query, string $search): Builder => $query->where('tax_rate_name', 'like', "%{$search}%"));
    }
}
