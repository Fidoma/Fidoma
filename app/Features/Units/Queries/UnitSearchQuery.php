<?php

namespace App\Features\Units\Queries;

use App\Features\Units\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

final class UnitSearchQuery
{
    /** @return Builder<Unit> */
    public function query(): Builder
    {
        return Unit::query();
    }
}
