<?php

namespace App\Features\Quotes\Models;

use App\Features\Products\Models\Product;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuoteItem extends Model
{
    protected $table = 'ip_quote_items';

    protected $primaryKey = 'item_id';

    public $timestamps = false;

    protected $fillable = [
        'quote_id',
        'item_tax_rate_id',
        'item_tax_included',
        'item_product_id',
        'item_date_added',
        'item_name',
        'item_description',
        'item_quantity',
        'item_price',
        'item_discount_amount',
        'item_order',
        'item_product_unit',
        'item_product_unit_id',
    ];

    protected function casts(): array
    {
        return [
            'item_date_added' => 'date',
            'item_quantity' => 'decimal:2',
            'item_price' => 'decimal:2',
            'item_discount_amount' => 'decimal:2',
            'item_tax_included' => 'boolean',
            'item_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteItem $item): void {
            $item->item_date_added ??= now()->toDateString();
        });

        static::saving(function (QuoteItem $item): void {
            if ($item->item_product_unit_id) {
                $item->item_product_unit = Unit::query()
                    ->whereKey($item->item_product_unit_id)
                    ->value('unit_name');
            } else {
                $item->item_product_unit = null;
            }
        });

        static::saved(fn (QuoteItem $item) => $item->recalculateAmount());
        static::deleted(fn (QuoteItem $item) => $item->amount()->delete());
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'quote_id');
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'item_tax_rate_id', 'tax_rate_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_product_id', 'product_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'item_product_unit_id', 'unit_id');
    }

    /** @return HasOne<QuoteItemAmount, $this> */
    public function amount(): HasOne
    {
        return $this->hasOne(QuoteItemAmount::class, 'item_id', 'item_id');
    }

    public function recalculateAmount(): QuoteItemAmount
    {
        $taxPercent = (float) ($this->taxRate()->value('tax_rate_percent') ?? 0);
        $amounts = app(QuoteAmountCalculator::class)->calculate([[
            'quantity' => $this->item_quantity,
            'price' => $this->item_price,
            'tax_percent' => $taxPercent,
            'tax_included' => $this->item_tax_included,
            'discount_amount' => $this->item_discount_amount,
        ]]);
        $itemAmount = $amounts['items'][0];

        return $this->amount()->updateOrCreate([], [
            'item_subtotal' => $itemAmount['subtotal'],
            'item_tax_total' => $itemAmount['tax'],
            'item_discount' => $itemAmount['discount'],
            'item_total' => $itemAmount['total'],
        ]);
    }
}
