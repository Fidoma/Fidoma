<?php

namespace App\Features\Clients\Filament\Resources\Clients\Tables;

use App\Features\Clients\Actions\ArchiveClient;
use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Client $record): string => ClientResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('client_number')
                    ->label(__('clients::messages.fields.number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label(__('clients::messages.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_email')
                    ->label(__('clients::messages.fields.email'))
                    ->searchable(),
                TextColumn::make('client_phone')
                    ->label(__('clients::messages.fields.phone'))
                    ->searchable(),
                TextColumn::make('client_city')
                    ->label(__('clients::messages.fields.city'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('client_active')
                    ->label(__('clients::messages.fields.active'))
                    ->boolean(),
                TextColumn::make('client_date_modified')
                    ->label(__('clients::messages.fields.modified'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('client_number')
                    ->label(__('clients::messages.fields.number'))
                    ->schema([TextInput::make('value')->label(__('clients::messages.fields.number'))])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('client_number', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
                Filter::make('client_name')
                    ->label(__('clients::messages.fields.name'))
                    ->schema([TextInput::make('value')->label(__('clients::messages.fields.name'))])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('client_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label(__('clients::messages.actions.create_client'))
                    ->icon(Heroicon::Plus)
                    ->url(ClientResource::getUrl('create')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label(__('clients::messages.actions.edit'))
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('createInvoice')
                        ->label(__('clients::messages.actions.create_invoice'))
                        ->icon(Heroicon::OutlinedDocumentCurrencyEuro)
                        ->url(fn (Client $record): string => InvoiceResource::getUrl('create', [
                            'client_id' => $record->getKey(),
                        ])),
                    Action::make('createQuote')
                        ->label(__('clients::messages.actions.create_quote'))
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->url(fn (Client $record): string => QuoteResource::getUrl('create', [
                            'client_id' => $record->getKey(),
                        ])),
                    Action::make('archive')
                        ->label(__('clients::messages.actions.archive'))
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('clients::messages.archive.heading'))
                        ->modalDescription(__('clients::messages.archive.description'))
                        ->modalSubmitActionLabel(__('clients::messages.actions.archive'))
                        ->visible(fn (Client $record): bool => $record->client_active)
                        ->action(function (Client $record): void {
                            app(ArchiveClient::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title(__('clients::messages.archive.success'))
                                ->send();
                        }),
                ])
                    ->label(__('clients::messages.actions.options'))
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->tooltip(__('clients::messages.actions.options'))
                    ->iconButton(),
            ]);
    }
}
