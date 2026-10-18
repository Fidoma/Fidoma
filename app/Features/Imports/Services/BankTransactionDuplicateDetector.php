<?php

namespace App\Features\Imports\Services;

use App\Features\BankTransactions\Models\BankTransaction;

final class BankTransactionDuplicateDetector
{
    /** @param array<string, mixed> $attributes */
    public function status(array $attributes): string
    {
        if ($this->isExactDuplicate($attributes)) {
            return 'duplicate';
        }

        $possibleDuplicate = BankTransaction::query()
            ->where('account_iban', $attributes['account_iban'])
            ->whereDate('booking_date', $attributes['booking_date'])
            ->where('amount', $attributes['amount'])
            ->where('currency', $attributes['currency'])
            ->exists();

        return $possibleDuplicate ? 'possible_duplicate' : 'new';
    }

    /** @param array<string, mixed> $attributes */
    private function isExactDuplicate(array $attributes): bool
    {
        if (filled($attributes['transaction_reference']) && BankTransaction::query()
            ->where('transaction_reference', $attributes['transaction_reference'])
            ->exists()) {
            return true;
        }

        if (filled($attributes['bank_transaction_id']) && BankTransaction::query()
            ->where('account_iban', $attributes['account_iban'])
            ->where('bank_transaction_id', $attributes['bank_transaction_id'])
            ->exists()) {
            return true;
        }

        return filled($attributes['end_to_end_id']) && BankTransaction::query()
            ->where('account_iban', $attributes['account_iban'])
            ->where('end_to_end_id', $attributes['end_to_end_id'])
            ->where('amount', $attributes['amount'])
            ->where('currency', $attributes['currency'])
            ->exists();
    }
}
