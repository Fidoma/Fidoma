<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CashflowChart;
use App\Filament\Widgets\DashboardKpis;
use App\Filament\Widgets\DashboardTasks;
use App\Filament\Widgets\OperationsOverview;
use App\Filament\Widgets\RevenueChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected static ?string $title = 'Dashboard';

    public function mount(): void
    {
        $this->filters ??= ['period' => 'this_month'];
    }

    /** @return array<class-string<Widget>> */
    public function getWidgets(): array
    {
        return [
            DashboardKpis::class,
            RevenueChart::class,
            CashflowChart::class,
            OperationsOverview::class,
            DashboardTasks::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'xl' => 2];
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Zeitraum')
                ->icon('heroicon-o-calendar-days')
                ->fillForm(fn (): array => $this->filters ?: ['period' => 'this_month'])
                ->schema([
                    Select::make('period')
                        ->label('Zeitraum')
                        ->options([
                            'this_month' => 'Dieser Monat',
                            'last_month' => 'Letzter Monat',
                            'this_quarter' => 'Dieses Quartal',
                            'this_year' => 'Dieses Jahr',
                            'custom' => 'Benutzerdefiniert',
                        ])
                        ->default('this_month')
                        ->required()
                        ->live(),
                    DatePicker::make('start_date')
                        ->label('Von')
                        ->visible(fn ($get): bool => $get('period') === 'custom')
                        ->required(fn ($get): bool => $get('period') === 'custom'),
                    DatePicker::make('end_date')
                        ->label('Bis')
                        ->visible(fn ($get): bool => $get('period') === 'custom')
                        ->required(fn ($get): bool => $get('period') === 'custom')
                        ->afterOrEqual('start_date'),
                ]),
        ];
    }
}
