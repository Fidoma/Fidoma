<?php

namespace App\Features\Products\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class ProductsCluster extends Cluster
{
    protected static ?string $navigationLabel = 'Produkte';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
