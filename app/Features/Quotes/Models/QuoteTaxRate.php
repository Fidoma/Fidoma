<?php

namespace App\Features\Quotes\Models;

use App\Features\TaxRates\Models\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteTaxRate extends Model
{
    protected $table = 'ip_quote_tax_rates';

    protected $primaryKey = 'quote_tax_rate_id';

    public $timestamps = false;

    protected $fillable = [
        'quote_id',
        'tax_rate_id',
        'include_item_tax',
        'quote_tax_rate_amount',
    ];

    protected function casts(): array
    {
        return [
            'include_item_tax' => 'boolean',
            'quote_tax_rate_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'quote_id');
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }
}
