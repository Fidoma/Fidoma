<?php

namespace App\Features\Units\Actions;

use App\Features\Units\Models\Unit;

final class CreateUnit
{
    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Unit
    {
        return Unit::query()->create($attributes);
    }
}
