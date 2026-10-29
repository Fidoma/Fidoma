<?php

namespace App\Features\Settings\Filament\Concerns;

use App\Features\Settings\Filament\Widgets\TemplateTabs;
use Filament\Widgets\Widget;

trait HasTemplateTabs
{
    /** @return array<class-string<Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [TemplateTabs::class];
    }
}
