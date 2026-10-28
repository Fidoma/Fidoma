<?php

namespace App\Features\ArtificialIntelligence\Policies;

use App\Features\ArtificialIntelligence\Models\AiTemplate;
use App\Models\User;

final class AiTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiTemplate $template): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AiTemplate $template): bool
    {
        return true;
    }

    public function delete(User $user, AiTemplate $template): bool
    {
        return true;
    }
}
