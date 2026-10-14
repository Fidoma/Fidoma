<?php

namespace App\Features\Invoices\Models;

use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Products\Models\Product;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvoiceItem extends Model
{
    protected $table = 'ip_invoice_items';

    protected $primaryKey = 'item_id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id', 'item_tax_rate_id', 'item_tax_included', 'item_product_id', 'item_date_added',
        'item_task_id', 'item_name', 'item_description', 'item_quantity', 'item_price',
        'item_discount_amount', 'item_order', 'item_is_recurring', 'item_product_unit',
        'item_product_unit_id', 'item_date',
    ];

    protected function casts(): array
    {
        return [
            'item_date_added' => 'date', 'item_date' => 'date', 'item_tax_included' => 'boolean',
            'item_quantity' => 'decimal:2', 'item_price' => 'decimal:2',
            'item_discount_amount' => 'decimal:2', 'item_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (InvoiceItem $item) => $item->item_date_added ??= now()->toDateString());
        static::saving(function (InvoiceItem $item): void {
            $item->item_product_unit = $item->item_product_unit_id
                ? Unit::query()->whereKey($item->item_product_unit_id)->value('unit_name')
                : null;
        });
        static::saved(fn (InvoiceItem $item) => $item->recalculateAmount());
        static::deleted(fn (InvoiceItem $item) => $item->amount()->delete());
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
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

    /** @return HasOne<InvoiceItemAmount, $this> */
    public function amount(): HasOne
    {
        return $this->hasOne(InvoiceItemAmount::class, 'item_id', 'item_id');
    }

    public function recalculateAmount(): InvoiceItemAmount
    {
        $sign = $this->invoice()->value('invoice_type') === InvoiceType::Invoice->value ? 1 : -1;
        $amounts = app(QuoteAmountCalculator::class)->calculate([[
            'quantity' => $this->item_quantity, 'price' => $this->item_price,
            'tax_percent' => $this->taxRate()->value('tax_rate_percent') ?? 0,
            'tax_included' => $this->item_tax_included, 'discount_amount' => $this->item_discount_amount,
        ]], sign: $sign);
        $item = $amounts['items'][0];

        return $this->amount()->updateOrCreate([], [
            'item_subtotal' => $item['subtotal'], 'item_tax_total' => $item['tax'],
            'item_discount' => $item['discount'], 'item_total' => $item['total'],
        ]);
    }
}
