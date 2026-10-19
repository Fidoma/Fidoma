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
                TextColumn::make('client_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_email')
                    ->label('E-Mail')
                    ->searchable(),
                TextColumn::make('client_phone')
                    ->label('Telefon')
                    ->searchable(),
                TextColumn::make('client_city')
                    ->label('Ort')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('client_active')
                    ->label('Aktiv')
                    ->boolean(),
                TextColumn::make('client_date_modified')
                    ->label('Geändert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('client_name')
                    ->label('Name')
                    ->schema([TextInput::make('value')->label('Name')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('client_name', 'like', '%'.trim((string) $data['value']).'%'),
                    )),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Kunde')
                    ->icon(Heroicon::Plus)
                    ->url(ClientResource::getUrl('create')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Bearbeiten')
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('createInvoice')
                        ->label('Rechnung erstellen')
                        ->icon(Heroicon::OutlinedDocumentCurrencyEuro)
                        ->url(fn (Client $record): string => InvoiceResource::getUrl('create', [
                            'client_id' => $record->getKey(),
                        ])),
                    Action::make('createQuote')
                        ->label('Angebot erstellen')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->url(fn (Client $record): string => QuoteResource::getUrl('create', [
                            'client_id' => $record->getKey(),
                        ])),
                    Action::make('archive')
                        ->label('Archivieren')
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Kunden archivieren')
                        ->modalDescription('Der Kunde und alle zugehörigen Angebote, Rechnungen und Zahlungen bleiben erhalten.')
                        ->modalSubmitActionLabel('Archivieren')
                        ->visible(fn (Client $record): bool => $record->client_active)
                        ->action(function (Client $record): void {
                            app(ArchiveClient::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title('Kunde wurde archiviert.')
                                ->send();
                        }),
                ])
                    ->label('Optionen')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->tooltip('Optionen')
                    ->iconButton(),
            ]);
    }
}
