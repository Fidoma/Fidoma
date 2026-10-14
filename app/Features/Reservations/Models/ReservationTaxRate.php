<?php

namespace App\Features\Reservations\Models;

use App\Features\TaxRates\Models\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationTaxRate extends Model
{
    protected $table = 'ip_reservation_tax_rates';

    protected $primaryKey = 'reservation_tax_rate_id';

    public $timestamps = false;

    protected $fillable = ['reservation_id', 'tax_rate_id', 'include_item_tax', 'reservation_tax_rate_amount'];

    protected function casts(): array
    {
        return ['include_item_tax' => 'boolean', 'reservation_tax_rate_amount' => 'decimal:2'];
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }
}
