<?php

namespace App\Features\Reservations\Models;

use App\Features\Clients\Models\Client;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Invoices\Models\Invoice;
use App\Features\Reservations\Enums\ReservationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $table = 'ip_reservations';

    protected $primaryKey = 'reservation_id';

    public const CREATED_AT = 'reservation_date_created';

    public const UPDATED_AT = 'reservation_date_modified';

    protected $fillable = [
        'invoice_id', 'user_id', 'client_id', 'invoice_group_id', 'reservation_status_id',
        'reservation_date_created', 'reservation_date_expires', 'reservation_number',
        'reservation_discount_amount', 'reservation_discount_percent', 'reservation_url_key', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'reservation_status_id' => ReservationStatus::class,
            'reservation_date_created' => 'date',
            'reservation_date_modified' => 'datetime',
            'reservation_date_expires' => 'date',
            'reservation_discount_amount' => 'decimal:2',
            'reservation_discount_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Reservation $reservation): void {
            $reservation->items()->get()->each->delete();
            $reservation->reservationTaxRates()->delete();
            $reservation->amount()->delete();
        });
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<InvoiceGroup, $this> */
    public function invoiceGroup(): BelongsTo
    {
        return $this->belongsTo(InvoiceGroup::class, 'invoice_group_id', 'invoice_group_id');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /** @return HasMany<ReservationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class, 'reservation_id', 'reservation_id')->orderBy('item_order');
    }

    /** @return HasMany<ReservationTaxRate, $this> */
    public function reservationTaxRates(): HasMany
    {
        return $this->hasMany(ReservationTaxRate::class, 'reservation_id', 'reservation_id');
    }

    /** @return HasOne<ReservationAmount, $this> */
    public function amount(): HasOne
    {
        return $this->hasOne(ReservationAmount::class, 'reservation_id', 'reservation_id');
    }
}
