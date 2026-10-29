<?php

namespace App\Features\Settings\Filament\Widgets;

use Filament\Widgets\Widget;

class TemplateTabs extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'settings::template-tabs';
}
