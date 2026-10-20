<?php

namespace App\Features\TradeFairs\Filament\Pages;

use App\Filament\Concerns\WithoutPageHeading;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class TradeFairs extends Page
{
    use WithoutPageHeading;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'trade-fairs';

    protected string $view = 'trade-fairs::trade-fairs';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string
    {
        return __('trade-fairs::messages.upcoming');
    }
}
