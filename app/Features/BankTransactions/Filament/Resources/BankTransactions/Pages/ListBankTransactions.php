<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions\Pages;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Features\BankTransactions\Models\BankTransaction;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBankTransactions extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = BankTransactionResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $all = BankTransaction::query()->count();
        $assigned = BankTransaction::query()->whereHas('mappings')->count();

        return [
            'all' => Tab::make('Alle')
                ->badge($all)
                ->badgeColor('gray'),
            'open' => Tab::make('Offen')
                ->badge($all - $assigned)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDoesntHave('mappings')),
            'assigned' => Tab::make('Zugeordnet')
                ->badge($assigned)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('mappings')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }
}
