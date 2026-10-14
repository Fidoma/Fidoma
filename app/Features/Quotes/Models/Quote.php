<?php

namespace App\Features\Quotes\Models;

use App\Features\Clients\Models\Client;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    protected $table = 'ip_quotes';

    protected $primaryKey = 'quote_id';

    public const CREATED_AT = 'quote_date_created';

    public const UPDATED_AT = 'quote_date_modified';

    protected $fillable = [
        'invoice_id',
        'user_id',
        'client_id',
        'invoice_group_id',
        'quote_status_id',
        'is_archived',
        'quote_date_created',
        'quote_date_expires',
        'quote_number',
        'quote_discount_amount',
        'quote_discount_percent',
        'quote_url_key',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quote_status_id' => QuoteStatus::class,
            'is_archived' => 'boolean',
            'quote_date_created' => 'date',
            'quote_date_modified' => 'datetime',
            'quote_date_expires' => 'date',
            'quote_discount_amount' => 'decimal:2',
            'quote_discount_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Quote $quote): void {
            $quote->items()->get()->each->delete();
            $quote->quoteTaxRates()->delete();
            $quote->amount()->delete();
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

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quote_id', 'quote_id')->orderBy('item_order');
    }

    /** @return HasMany<QuoteTaxRate, $this> */
    public function quoteTaxRates(): HasMany
    {
        return $this->hasMany(QuoteTaxRate::class, 'quote_id', 'quote_id');
    }

    /** @return HasOne<QuoteAmount, $this> */
    public function amount(): HasOne
    {
        return $this->hasOne(QuoteAmount::class, 'quote_id', 'quote_id');
    }

    public function subtotal(): float
    {
        return (float) ($this->amount()->value('quote_item_subtotal') ?? 0);
    }

    public function total(): float
    {
        return (float) ($this->amount()->value('quote_total') ?? 0);
    }
}
