<?php

namespace App\Features\Invoices\Filament\Resources\Invoices\Pages;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Filament\Concerns\WithoutPageHeading;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    use WithoutPageHeading;

    protected static string $resource = InvoiceResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $counts = Invoice::query()
            ->where('is_archived', false)
            ->selectRaw('invoice_status_id, COUNT(*) as aggregate')
            ->groupBy('invoice_status_id')
            ->pluck('aggregate', 'invoice_status_id');

        $tabs = [
            'all' => Tab::make('Alle')
                ->badge((int) $counts->sum())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('is_archived', false)),
        ];

        foreach (InvoiceStatus::cases() as $status) {
            $tabs[$status->name] = Tab::make($status->label())
                ->badge((int) ($counts[$status->value] ?? 0))
                ->badgeColor($status->color())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('is_archived', false)
                    ->where('invoice_status_id', $status->value));
        }

        $overdueQuery = fn (Builder $query): Builder => $query
            ->where('is_archived', false)
            ->where('invoice_status_id', InvoiceStatus::Open->value)
            ->whereDate('invoice_date_due', '<', today())
            ->whereHas('amount', fn (Builder $query): Builder => $query
                ->where('invoice_balance', '>', 0));

        $tabs['overdue'] = Tab::make('Überfällig')
            ->badge($overdueQuery(Invoice::query())->count())
            ->badgeColor('danger')
            ->modifyQueryUsing($overdueQuery);

        foreach ([
            [InvoiceType::Credit, 'Gutschrift'],
            [InvoiceType::Cancellation, 'Storniert'],
        ] as [$type, $label]) {
            $typeQuery = fn (Builder $query): Builder => $query
                ->where('is_archived', false)
                ->where('invoice_type', $type);

            $tabs[$type->value] = Tab::make($label)
                ->badge($typeQuery(Invoice::query())->count())
                ->badgeColor($type->color())
                ->modifyQueryUsing($typeQuery);
        }

        $tabs['archived'] = Tab::make('Archiviert')
            ->badge(Invoice::query()->where('is_archived', true)->count())
            ->badgeColor('gray')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('is_archived', true));

        return $tabs;
    }
}
