<?php

namespace App\Features\Quotes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAmount extends Model
{
    protected $table = 'ip_quote_amounts';

    protected $primaryKey = 'quote_amount_id';

    public $timestamps = false;

    protected $fillable = [
        'quote_id',
        'quote_item_subtotal',
        'quote_item_tax_total',
        'quote_tax_total',
        'quote_total',
    ];

    protected function casts(): array
    {
        return [
            'quote_item_subtotal' => 'decimal:2',
            'quote_item_tax_total' => 'decimal:2',
            'quote_tax_total' => 'decimal:2',
            'quote_total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'quote_id');
    }
}
