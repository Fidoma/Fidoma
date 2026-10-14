<?php

namespace App\Features\Families\Queries;

use App\Features\Families\Models\Family;
use Illuminate\Database\Eloquent\Builder;

final class FamilySearchQuery
{
    /**
     * @return Builder<Family>
     */
    public function query(?string $search = null): Builder
    {
        return Family::query()
            ->when($search, fn (Builder $query, string $search): Builder => $query->where('family_name', 'like', "%{$search}%"));
    }
}
