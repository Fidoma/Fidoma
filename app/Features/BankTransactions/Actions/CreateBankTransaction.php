<?php

namespace App\Features\BankTransactions\Actions;

use App\Features\BankTransactions\Models\BankTransaction;

final class CreateBankTransaction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): BankTransaction
    {
        $attributes['currency'] = mb_strtoupper((string) ($attributes['currency'] ?? 'EUR'));
        $attributes['transaction_reference'] ??= bin2hex(random_bytes(32));
        $attributes['validated'] ??= false;

        return BankTransaction::query()->create($attributes);
    }
}
