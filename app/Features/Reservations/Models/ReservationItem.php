<?php

namespace App\Features\Reservations\Models;

use App\Features\Products\Models\Product;
use App\Features\Quotes\Services\QuoteAmountCalculator;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class ReservationItem extends Model
{
    protected $table = 'ip_reservation_items';

    protected $primaryKey = 'item_id';

    public $timestamps = false;

    protected $fillable = [
        'reservation_id', 'room_id', 'arrival_date', 'departure_date', 'item_tax_rate_id',
        'item_tax_included', 'item_product_id', 'item_date_added', 'item_name', 'item_description',
        'item_quantity', 'item_price', 'item_discount_amount', 'item_order', 'item_product_unit',
        'item_product_unit_id',
    ];

    protected function casts(): array
    {
        return [
            'arrival_date' => 'date', 'departure_date' => 'date', 'item_date_added' => 'date',
            'item_tax_included' => 'boolean', 'item_quantity' => 'decimal:2', 'item_price' => 'decimal:2',
            'item_discount_amount' => 'decimal:2', 'item_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (ReservationItem $item) => $item->item_date_added ??= now()->toDateString());
        static::saving(function (ReservationItem $item): void {
            if ($item->arrival_date && $item->departure_date && ! $item->departure_date->isAfter($item->arrival_date)) {
                throw ValidationException::withMessages([
                    'departure_date' => 'Die Abreise muss nach der Anreise liegen.',
                ]);
            }

            $item->item_product_unit = $item->item_product_unit_id
                ? Unit::query()->whereKey($item->item_product_unit_id)->value('unit_name')
                : null;
        });
        static::saved(fn (ReservationItem $item) => $item->recalculateAmount());
        static::deleted(fn (ReservationItem $item) => $item->amount()->delete());
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'reservation_id');
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
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

    /** @return HasOne<ReservationItemAmount, $this> */
    public function amount(): HasOne
    {
        return $this->hasOne(ReservationItemAmount::class, 'item_id', 'item_id');
    }

    public function recalculateAmount(): ReservationItemAmount
    {
        $amounts = app(QuoteAmountCalculator::class)->calculate([[
            'quantity' => $this->item_quantity,
            'price' => $this->item_price,
            'tax_percent' => $this->taxRate()->value('tax_rate_percent') ?? 0,
            'tax_included' => $this->item_tax_included,
            'discount_amount' => $this->item_discount_amount,
        ]]);
        $item = $amounts['items'][0];

        return $this->amount()->updateOrCreate([], [
            'item_subtotal' => $item['subtotal'], 'item_tax_total' => $item['tax'],
            'item_discount' => $item['discount'], 'item_total' => $item['total'],
        ]);
    }
}
