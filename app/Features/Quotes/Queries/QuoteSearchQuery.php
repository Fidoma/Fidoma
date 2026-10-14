<?php

namespace App\Features\Quotes\Queries;

use App\Features\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Builder;

final class QuoteSearchQuery
{
    /** @return Builder<Quote> */
    public function query(?string $search = null): Builder
    {
        return Quote::query()
            ->with('client')
            ->when($search, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('quote_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $query) => $query->where('client_name', 'like', "%{$search}%"));
            }));
    }
}
