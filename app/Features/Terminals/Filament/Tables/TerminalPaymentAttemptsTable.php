<?php

namespace App\Features\Terminals\Filament\Tables;

use App\Features\Terminals\Enums\TerminalPaymentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TerminalPaymentAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('user_id', auth()->id())
                ->with(['invoice.client']))
            ->defaultSort('started_at', 'desc')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('started_at')
                    ->label(__('terminals::messages.history.columns.started_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label(__('terminals::messages.history.columns.invoice'))
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.client.client_name')
                    ->label(__('terminals::messages.history.columns.customer'))
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('terminals::messages.history.columns.amount'))
                    ->money('EUR', locale: 'de')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('terminals::messages.history.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn (TerminalPaymentStatus $state): string => $state->label())
                    ->color(fn (TerminalPaymentStatus $state): string => $state->color()),
                TextColumn::make('gateway_reference')
                    ->label(__('terminals::messages.history.columns.reference'))
                    ->placeholder('–')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('status_message')
                    ->label(__('terminals::messages.history.columns.message'))
                    ->placeholder('–')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('terminals::messages.history.filters.status'))
                    ->options(collect(TerminalPaymentStatus::cases())
                        ->mapWithKeys(fn (TerminalPaymentStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                Filter::make('started_at')
                    ->schema([
                        DatePicker::make('from')->label(__('terminals::messages.history.filters.from')),
                        DatePicker::make('until')->label(__('terminals::messages.history.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('started_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('started_at', '<=', $date))),
            ])
            ->searchable()
            ->searchPlaceholder(__('terminals::messages.history.search'))
            ->paginated([5, 10, 20, 50])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('terminals::messages.history.empty'))
            ->emptyStateDescription(__('terminals::messages.history.empty_description'))
            ->emptyStateIcon('heroicon-o-clock');
    }
}
