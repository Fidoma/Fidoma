<?php

namespace App\Features\BankTransactions\Models;

use App\Features\BankTransactions\Policies\BankTransactionPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(BankTransactionPolicy::class)]
class BankTransaction extends Model
{
    protected $table = 'ip_bank_transactions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'account_iban',
        'booking_date',
        'value_date',
        'amount',
        'currency',
        'purpose',
        'counterparty_name',
        'counterparty_iban',
        'booking_text',
        'transaction_reference',
        'bank_transaction_id',
        'bank_reference',
        'end_to_end_id',
        'pending',
        'validated',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'value_date' => 'date',
            'amount' => 'decimal:4',
            'pending' => 'boolean',
            'validated' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return HasMany<BankTransactionMapping, $this> */
    public function mappings(): HasMany
    {
        return $this->hasMany(BankTransactionMapping::class, 'bank_transaction_id');
    }

    public function statusLabel(): string
    {
        if ($this->pending) {
            return 'Vorgemerkt';
        }

        $hasMappings = array_key_exists('mappings_count', $this->attributes)
            ? (int) $this->attributes['mappings_count'] > 0
            : $this->mappings()->exists();

        return $hasMappings ? 'Zugeordnet' : 'Offen';
    }

    public function assignmentFingerprint(): string
    {
        return hash('sha256', implode('|', [
            $this->getKey(),
            $this->amount,
            $this->currency,
            $this->booking_date?->toDateString(),
            (int) $this->pending,
            (int) $this->validated,
        ]));
    }
}
