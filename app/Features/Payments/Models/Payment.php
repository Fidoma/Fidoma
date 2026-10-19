<?php

namespace App\Features\Payments\Models;

use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Invoices\Models\Invoice;
use App\Features\PaymentMethods\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $table = 'ip_payments';

    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'payment_method_id',
        'payment_date',
        'payment_amount',
        'payment_note',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'payment_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    /** @return HasMany<BankTransactionMapping, $this> */
    public function bankTransactionMappings(): HasMany
    {
        return $this->hasMany(BankTransactionMapping::class, 'payment_id', 'payment_id');
    }
}
