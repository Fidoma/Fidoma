<?php

namespace App\Features\Invoices\Policies;

use App\Features\Invoices\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return true;
    }
}
