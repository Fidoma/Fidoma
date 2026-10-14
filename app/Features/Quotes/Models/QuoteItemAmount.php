<?php

namespace App\Features\Quotes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItemAmount extends Model
{
    protected $table = 'ip_quote_item_amounts';

    protected $primaryKey = 'item_amount_id';

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'item_subtotal',
        'item_tax_total',
        'item_discount',
        'item_total',
    ];

    protected function casts(): array
    {
        return [
            'item_subtotal' => 'decimal:2',
            'item_tax_total' => 'decimal:2',
            'item_discount' => 'decimal:2',
            'item_total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<QuoteItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class, 'item_id', 'item_id');
    }
}
