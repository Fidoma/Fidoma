<?php

namespace App\Features\Invoices\Filament\Resources\Invoices\Tables;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Invoices\Actions\ArchiveInvoice;
use App\Features\Invoices\Actions\CreateInvoiceCounterpart;
use App\Features\Invoices\Actions\RecordInvoicePayment;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Actions\InvoiceEmailAction;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\PaymentMethods\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('invoice_date_created', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Rechnung')
                    ->badge()
                    ->color(fn (Invoice $record): string => $record->invoice_type->color())
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('edit', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.client_name')
                    ->label('Kunde')
                    ->url(fn (Invoice $record): string => ClientResource::getUrl('view', ['record' => $record->client_id]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date_created')->label('Datum')->date('d.m.Y')->sortable(),
                TextColumn::make('invoice_date_due')->label('Fällig am')->date('d.m.Y')->sortable()
                    ->color(fn (Invoice $record): ?string => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('invoice_status_id')->label('Status')->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->color()),
                TextColumn::make('amount.invoice_total')->label('Gesamt')->money('EUR', locale: 'de')->sortable(),
                TextColumn::make('amount.invoice_paid')->label('Bezahlt')->money('EUR', locale: 'de'),
                TextColumn::make('amount.invoice_balance')->label('Offen')->money('EUR', locale: 'de')->sortable(),
            ])
            ->recordUrl(null)
            ->toolbarActions([
                CreateAction::make()
                    ->label('Rechnung')
                    ->icon(Heroicon::Plus)
                    ->url(InvoiceResource::getUrl('create')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewPdf')
                        ->label('PDF anzeigen')
                        ->icon(Heroicon::OutlinedDocumentArrowDown)
                        ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record]))
                        ->openUrlInNewTab(),
                    Action::make('downloadXRechnung')
                        ->label('XRechnung herunterladen')
                        ->icon(Heroicon::OutlinedCodeBracketSquare)
                        ->url(fn (Invoice $record): string => route('invoices.xrechnung', ['invoice' => $record]))
                        ->openUrlInNewTab(),
                    InvoiceEmailAction::make(),
                    self::recordPaymentAction(),
                    self::counterpartAction('createCredit', InvoiceType::Credit),
                    self::counterpartAction('createCancellation', InvoiceType::Cancellation),
                    Action::make('archive')
                        ->label('Archivieren')
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Rechnung archivieren')
                        ->modalDescription('Die Rechnung und alle zugehörigen Positionen, Steuern und Zahlungen bleiben vollständig erhalten.')
                        ->modalSubmitActionLabel('Archivieren')
                        ->visible(fn (Invoice $record): bool => ! $record->is_archived)
                        ->action(function (Invoice $record): void {
                            app(ArchiveInvoice::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title('Rechnung wurde archiviert.')
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

    private static function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label('Zahlung eingeben')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('primary')
            ->visible(fn (Invoice $record): bool => $record->invoice_type === InvoiceType::Invoice
                && ! $record->is_archived
                && $record->invoice_status_id !== InvoiceStatus::Paid
                && (float) ($record->amount?->invoice_balance ?? 0) > 0)
            ->modalHeading(fn (Invoice $record): string => 'Zahlung für Rechnung '.$record->invoice_number)
            ->modalSubmitActionLabel('Zahlung speichern')
            ->schema([
                DatePicker::make('payment_date')
                    ->label('Zahlungsdatum')
                    ->default(now()->toDateString())
                    ->required(),
                Select::make('payment_method_id')
                    ->label('Zahlungsart')
                    ->options(fn () => PaymentMethod::query()
                        ->orderBy('payment_method_name')
                        ->pluck('payment_method_name', 'payment_method_id'))
                    ->default(fn (Invoice $record): ?int => $record->payment_method
                        ?: (app(InvoiceDefaults::class)->paymentMethodId() ?: null))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('payment_amount')
                    ->label('Betrag')
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(fn (Invoice $record): float => (float) ($record->amount?->invoice_balance ?? 0))
                    ->default(fn (Invoice $record): float => (float) ($record->amount?->invoice_balance ?? 0))
                    ->suffix('€')
                    ->required(),
                TextInput::make('payment_note')
                    ->label('Notiz')
                    ->default('')
                    ->maxLength(1000),
            ])
            ->action(function (Invoice $record, array $data): void {
                app(RecordInvoicePayment::class)->handle($record, $data);

                Notification::make()
                    ->success()
                    ->title('Zahlung wurde gespeichert.')
                    ->send();
            });
    }

    private static function counterpartAction(string $name, InvoiceType $type): Action
    {
        return Action::make($name)
            ->label($type === InvoiceType::Credit ? 'Gutschrift erstellen' : 'Rechnung stornieren')
            ->icon($type === InvoiceType::Credit ? Heroicon::OutlinedDocumentMinus : Heroicon::OutlinedNoSymbol)
            ->color($type === InvoiceType::Credit ? 'success' : 'danger')
            ->visible(fn (Invoice $record): bool => $record->invoice_type === InvoiceType::Invoice
                && ! $record->is_archived)
            ->disabled(fn (Invoice $record): bool => $record->invoice_status_id === InvoiceStatus::Draft)
            ->tooltip(fn (Invoice $record): ?string => $record->invoice_status_id === InvoiceStatus::Draft
                ? 'Die Rechnung muss zuerst ausgestellt werden.'
                : null)
            ->requiresConfirmation()
            ->modalHeading($type === InvoiceType::Credit ? 'Gutschrift erstellen' : 'Rechnung stornieren')
            ->modalDescription(fn (Invoice $record): string => sprintf(
                '%s wird als negativer Entwurf mit direktem Bezug zu Rechnung %s erstellt.',
                $type->label(),
                $record->invoice_number,
            ))
            ->action(function (Action $action, Invoice $record) use ($type): void {
                $counterpart = app(CreateInvoiceCounterpart::class)->handle($record, $type);

                Notification::make()
                    ->success()
                    ->title($type->label().' wurde als Entwurf erstellt.')
                    ->send();

                $action->redirect(InvoiceResource::getUrl('edit', ['record' => $counterpart]));
            });
    }
}
