<?php

namespace App\Features\Units\Models;

use App\Features\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $table = 'ip_units';

    protected $primaryKey = 'unit_id';

    public $timestamps = false;

    protected $fillable = ['unit_name', 'unit_name_plrl'];

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id', 'unit_id');
    }
}
