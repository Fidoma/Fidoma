<?php

namespace App\Features\Families\Models;

use App\Features\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $table = 'ip_families';

    protected $primaryKey = 'family_id';

    public $timestamps = false;

    protected $fillable = [
        'family_name',
    ];

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'family_id', 'family_id');
    }
}
