<?php

namespace App\Features\Families\Actions;

use App\Features\Families\Models\Family;

final class CreateFamily
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Family
    {
        return Family::query()->create($attributes);
    }
}
