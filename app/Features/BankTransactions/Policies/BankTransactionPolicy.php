<?php

namespace App\Features\BankTransactions\Policies;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Models\User;

final class BankTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BankTransaction $bankTransaction): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BankTransaction $bankTransaction): bool
    {
        return true;
    }

    public function delete(User $user, BankTransaction $bankTransaction): bool
    {
        return true;
    }
}
