<?php

namespace App\Features\Quotes\Filament\Resources\Quotes\Pages;

use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Features\Quotes\Models\Quote;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuotes extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = QuoteResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $counts = Quote::query()
            ->where('is_archived', false)
            ->selectRaw('quote_status_id, COUNT(*) as aggregate')
            ->groupBy('quote_status_id')
            ->pluck('aggregate', 'quote_status_id');

        $tabs = [
            'all' => Tab::make('Alle')
                ->badge((int) $counts->sum())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('is_archived', false)),
        ];

        foreach (QuoteStatus::cases() as $status) {
            $tabs[$status->name] = Tab::make($status->label())
                ->badge((int) ($counts[$status->value] ?? 0))
                ->badgeColor($status->color())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('is_archived', false)
                    ->where('quote_status_id', $status->value));
        }

        $tabs['archived'] = Tab::make('Archiviert')
            ->badge(Quote::query()->where('is_archived', true)->count())
            ->badgeColor('gray')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('is_archived', true));

        return $tabs;
    }
}
