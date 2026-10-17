<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Willkommen zurück';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Melden Sie sich an, um Ihren Fidoma-Arbeitsbereich zu öffnen.';
    }
}
