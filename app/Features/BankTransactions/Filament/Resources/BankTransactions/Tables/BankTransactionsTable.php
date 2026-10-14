<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions\Tables;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Features\BankTransactions\Models\BankTransaction;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BankTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('booking_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('mappings'))
            ->recordUrl(fn (BankTransaction $record): string => BankTransactionResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('booking_date')
                    ->label('Buchungsdatum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('counterparty_name')
                    ->label('Gegenpartei')
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purpose')
                    ->label('Verwendungszweck')
                    ->placeholder('–')
                    ->searchable()
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('amount')
                    ->label('Betrag')
                    ->money(fn (BankTransaction $record): string => $record->currency, locale: 'de')
                    ->color(fn (BankTransaction $record): string => (float) $record->amount < 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (BankTransaction $record): string => $record->statusLabel())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Zugeordnet' => 'success',
                        'Vorgemerkt' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Banktransaktion')
                    ->icon(Heroicon::Plus)
                    ->url(BankTransactionResource::getUrl('create')),
            ]);
    }
}
