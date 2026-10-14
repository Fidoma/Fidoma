<?php

namespace App\Features\Products\Queries;

use App\Features\Products\Models\Product;
use Illuminate\Database\Eloquent\Builder;

final class ProductSearchQuery
{
    /**
     * @return Builder<Product>
     */
    public function query(?string $search = null): Builder
    {
        return Product::query()
            ->when($search, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('product_sku', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('product_description', 'like', "%{$search}%");
            }));
    }
}
