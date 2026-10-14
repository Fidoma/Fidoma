<?php

namespace App\Features\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAmount extends Model
{
    protected $table = 'ip_invoice_amounts';

    protected $primaryKey = 'invoice_amount_id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id', 'invoice_sign', 'invoice_item_subtotal', 'invoice_item_tax_total',
        'invoice_tax_total', 'invoice_total', 'invoice_paid', 'invoice_balance',
    ];

    protected function casts(): array
    {
        return [
            'invoice_item_subtotal' => 'decimal:2', 'invoice_item_tax_total' => 'decimal:2',
            'invoice_tax_total' => 'decimal:2', 'invoice_total' => 'decimal:2',
            'invoice_paid' => 'decimal:2', 'invoice_balance' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }
}
