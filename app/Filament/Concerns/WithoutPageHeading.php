<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

trait WithoutPageHeading
{
    public function getHeading(): string|Htmlable|null
    {
        return null;
    }
}
