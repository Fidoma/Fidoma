<?php

namespace App\Features\Authentication\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
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

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->default(config('auth.demo.email'));
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->default(config('auth.demo.password'));
    }
}
