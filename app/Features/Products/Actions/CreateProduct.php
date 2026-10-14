<?php

namespace App\Features\Products\Actions;

use App\Features\Products\Models\Product;

final class CreateProduct
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }
}
