<?php

namespace App\Features\Invoices\Models;

use App\Features\TaxRates\Models\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTaxRate extends Model
{
    protected $table = 'ip_invoice_tax_rates';

    protected $primaryKey = 'invoice_tax_rate_id';

    public $timestamps = false;

    protected $fillable = ['invoice_id', 'tax_rate_id', 'include_item_tax', 'invoice_tax_rate_amount'];

    protected function casts(): array
    {
        return ['include_item_tax' => 'boolean', 'invoice_tax_rate_amount' => 'decimal:2'];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }
}
