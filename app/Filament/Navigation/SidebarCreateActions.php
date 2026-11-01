<?php

namespace App\Filament\Navigation;

use Filament\Facades\Filament;

final class SidebarCreateActions
{
    /**
     * @return array<int, array{navigationUrl: string, createUrl: string, label: string}>
     */
    public function all(): array
    {
        $actions = [];
        $resources = Filament::getCurrentOrDefaultPanel()->getResources();

        usort(
            $resources,
            fn (string $first, string $second): int => ($first::getNavigationSort() ?? PHP_INT_MAX)
                <=> ($second::getNavigationSort() ?? PHP_INT_MAX),
        );

        foreach ($resources as $resource) {
            if (! $resource::hasPage('create') || ! $resource::canCreate()) {
                continue;
            }

            if (filled($cluster = $resource::getCluster())) {
                $navigationUrl = $cluster::getNavigationUrl();
            } else {
                if (! $resource::shouldRegisterNavigation() || ! $resource::canAccess()) {
                    continue;
                }

                $navigationItems = $resource::getNavigationItems();
                $navigationUrl = ($navigationItems[0] ?? null)?->getUrl();
            }

            if (blank($navigationUrl) || isset($actions[$navigationUrl])) {
                continue;
            }

            $actions[$navigationUrl] = [
                'navigationUrl' => $navigationUrl,
                'createUrl' => $resource::getUrl('create'),
                'label' => $resource::getModelLabel().' erstellen',
            ];
        }

        return array_values($actions);
    }
}
