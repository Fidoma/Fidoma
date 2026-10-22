<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions\Tables;

use App\Features\BankTransactions\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\Schemas\BankTransactionInfolist;
use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Documents\Filament\Pages\Documents;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BankTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('booking_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount('mappings')
                ->with(['mappings.payment.invoice.client', 'mappings.payment.invoice.amount', 'mappings.payment.paymentMethod']))
            ->recordUrl(null)
            ->recordAction('view')
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
            ->filters([
                Filter::make('counterparty_name')
                    ->label('Gegenpartei')
                    ->schema([TextInput::make('value')->label('Gegenpartei')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('counterparty_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Banktransaktion')
                    ->icon(Heroicon::Plus)
                    ->url(BankTransactionResource::getUrl('create')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('bank-transactions::messages.actions.view'))
                    ->extraAttributes(['style' => 'display: none'])
                    ->modalHeading(__('bank-transactions::messages.detail.heading'))
                    ->schema(fn (Schema $schema): Schema => BankTransactionInfolist::configure($schema))
                    ->slideOver()
                    ->modalWidth(Width::FourExtraLarge)
                    ->extraModalFooterActions([
                        Action::make('openAssignment')
                            ->label(__('bank-transactions::messages.actions.open_assignment'))
                            ->icon(Heroicon::OutlinedLink)
                            ->url(fn (BankTransaction $record): string => Documents::getUrl([
                                'activeTab' => (float) $record->amount >= 0 ? 'income' : 'expenses',
                            ])),
                    ]),
            ]);
    }
}
