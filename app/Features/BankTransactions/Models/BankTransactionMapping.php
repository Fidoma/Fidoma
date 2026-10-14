<?php

namespace App\Features\BankTransactions\Models;

use App\Features\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransactionMapping extends Model
{
    protected $table = 'ip_bank_transaction_mappings';

    protected $primaryKey = 'bank_transaction_mapping_id';

    public const CREATED_AT = 'bank_transaction_mapping_date_created';

    public const UPDATED_AT = 'bank_transaction_mapping_date_modified';

    protected $fillable = ['bank_transaction_id', 'payment_id', 'document_id'];

    protected function casts(): array
    {
        return [
            'bank_transaction_id' => 'integer',
            'payment_id' => 'integer',
            'document_id' => 'integer',
        ];
    }

    /** @return BelongsTo<BankTransaction, $this> */
    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'bank_transaction_id');
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }
}
