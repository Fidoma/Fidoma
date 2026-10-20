<?php

namespace App\Filament\Concerns;

use App\Features\Settings\Services\ModuleSettings;

trait HasConfigurableNavigation
{
    abstract protected static function navigationModule(): string;

    public static function shouldRegisterNavigation(): bool
    {
        return app(ModuleSettings::class)->isEnabled(static::navigationModule());
    }
}
