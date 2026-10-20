<?php

namespace App\Features\Terminals\Policies;

use App\Features\Terminals\Models\PaymentTerminal;
use App\Models\User;

final class PaymentTerminalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, PaymentTerminal $terminal): bool
    {
        return $terminal->user_id === $user->getKey();
    }

    public function update(User $user, PaymentTerminal $terminal): bool
    {
        return $this->view($user, $terminal);
    }

    public function delete(User $user, PaymentTerminal $terminal): bool
    {
        return $this->view($user, $terminal) && ! $terminal->paymentAttempts()->exists();
    }
}
