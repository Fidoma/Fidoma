<?php

namespace App\Features\Settings\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class SettingsCluster extends Cluster
{
    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $slug = 'settings';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
