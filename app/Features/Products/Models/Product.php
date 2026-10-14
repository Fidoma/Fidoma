<?php

namespace App\Features\Products\Models;

use App\Features\Families\Models\Family;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'ip_products';

    protected $primaryKey = 'product_id';

    public $timestamps = false;

    protected $fillable = [
        'family_id',
        'product_sku',
        'product_name',
        'product_description',
        'product_price',
        'purchase_price',
        'provider_name',
        'tax_rate_id',
        'unit_id',
        'product_tariff',
    ];

    protected function casts(): array
    {
        return [
            'family_id' => 'integer',
            'product_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'tax_rate_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id', 'family_id');
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }
}
