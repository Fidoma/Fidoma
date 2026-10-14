<?php

namespace App\Features\TaxRates\Models;

use App\Features\Products\Models\Product;
use App\Features\Quotes\Models\QuoteItem;
use App\Features\Quotes\Models\QuoteTaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends Model
{
    protected $table = 'ip_tax_rates';

    protected $primaryKey = 'tax_rate_id';

    public $timestamps = false;

    protected $fillable = [
        'tax_rate_name',
        'tax_rate_percent',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate_percent' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'tax_rate_id', 'tax_rate_id');
    }

    /** @return HasMany<QuoteItem, $this> */
    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'item_tax_rate_id', 'tax_rate_id');
    }

    /** @return HasMany<QuoteTaxRate, $this> */
    public function quoteTaxRates(): HasMany
    {
        return $this->hasMany(QuoteTaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }
}
