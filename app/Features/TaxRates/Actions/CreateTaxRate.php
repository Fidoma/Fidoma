<?php

namespace App\Features\TaxRates\Actions;

use App\Features\TaxRates\Models\TaxRate;

final class CreateTaxRate
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): TaxRate
    {
        return TaxRate::query()->create($attributes);
    }
}
