<?php

namespace App\Features\PaymentMethods\Models;

use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $table = 'ip_payment_methods';

    protected $primaryKey = 'payment_method_id';

    public $timestamps = false;

    protected $fillable = ['payment_method_name'];

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_method_id', 'payment_method_id');
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'payment_method', 'payment_method_id');
    }
}
