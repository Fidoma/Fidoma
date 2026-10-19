<?php

namespace App\Features\Clients\Filament\Resources\Clients\Pages;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = ClientResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $counts = Client::query()
            ->selectRaw('client_active, COUNT(*) as aggregate')
            ->groupBy('client_active')
            ->pluck('aggregate', 'client_active');

        return [
            'all' => Tab::make(__('clients::messages.tabs.all'))
                ->badge((int) $counts->sum())
                ->badgeColor('gray'),
            'active' => Tab::make(__('clients::messages.tabs.active'))
                ->badge((int) ($counts[1] ?? 0))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('client_active', true)),
            'archived' => Tab::make(__('clients::messages.tabs.archived'))
                ->badge((int) ($counts[0] ?? 0))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('client_active', false)),
        ];
    }
}
