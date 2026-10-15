<?php

namespace App\Features\Payments\Filament\Resources\Payments\Tables;

use App\Features\Invoices\Actions\RecordInvoicePayment;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['invoice.client', 'paymentMethod']))
            ->defaultSort('payment_date', 'desc')
            ->recordUrl(fn (Payment $record): string => InvoiceResource::getUrl('edit', ['record' => $record->invoice_id]))
            ->columns([
                TextColumn::make('payment_date')
                    ->label('Zahlungsdatum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('invoice.client.client_name')
                    ->label('Kunde')
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Rechnung')
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('paymentMethod.payment_method_name')
                    ->label('Zahlungsart')
                    ->placeholder('–'),
                TextColumn::make('payment_amount')
                    ->label('Betrag')
                    ->money('EUR', locale: 'de')
                    ->sortable(),
                TextColumn::make('payment_note')
                    ->label('Notiz')
                    ->placeholder('–')
                    ->searchable()
                    ->wrap(),
            ])
            ->toolbarActions([
                self::createPaymentAction(),
            ])
            ->searchable()
            ->searchPlaceholder('Kunde, Rechnung oder Notiz durchsuchen')
            ->paginated([10, 20, 50])
            ->defaultPaginationPageOption(20)
            ->emptyStateHeading('Keine Zahlungen vorhanden')
            ->emptyStateDescription('Erfasste und zugeordnete Zahlungen werden hier aufgelistet.')
            ->emptyStateIcon(Heroicon::OutlinedBanknotes);
    }

    private static function createPaymentAction(): Action
    {
        return Action::make('createPayment')
            ->label('Zahlung')
            ->icon(Heroicon::Plus)
            ->modalHeading('Zahlung hinzufügen')
            ->modalSubmitActionLabel('Zahlung speichern')
            ->schema([
                Select::make('invoice_id')
                    ->label('Rechnung')
                    ->options(fn (): array => self::openInvoices()
                        ->get()
                        ->mapWithKeys(fn (Invoice $invoice): array => [
                            $invoice->getKey() => self::invoiceOptionLabel($invoice),
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        $invoice = filled($state)
                            ? self::openInvoices()->find($state)
                            : null;

                        $set('payment_amount', $invoice?->amount?->invoice_balance);
                        $set('payment_method_id', $invoice?->payment_method
                            ?: (app(InvoiceDefaults::class)->paymentMethodId() ?: null));
                    })
                    ->required(),
                Select::make('payment_method_id')
                    ->label('Zahlungsart')
                    ->options(fn () => PaymentMethod::query()
                        ->orderBy('payment_method_name')
                        ->pluck('payment_method_name', 'payment_method_id'))
                    ->default(fn (): ?int => app(InvoiceDefaults::class)->paymentMethodId() ?: null)
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('payment_amount')
                    ->label('Zahlbetrag')
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(function (Get $get): ?float {
                        $invoiceId = $get('invoice_id');

                        if (blank($invoiceId)) {
                            return null;
                        }

                        return (float) Invoice::query()
                            ->with('amount')
                            ->find($invoiceId)?->amount?->invoice_balance;
                    })
                    ->suffix('€')
                    ->required(),
                DatePicker::make('payment_date')
                    ->label('Zahlungsdatum')
                    ->default(now()->toDateString())
                    ->required(),
                TextInput::make('payment_note')
                    ->label('Notiz')
                    ->default('')
                    ->maxLength(1000),
            ])
            ->action(function (array $data): void {
                $invoice = self::openInvoices()->findOrFail($data['invoice_id']);
                app(RecordInvoicePayment::class)->handle($invoice, $data);

                Notification::make()
                    ->success()
                    ->title('Zahlung wurde gespeichert.')
                    ->send();
            });
    }

    /** @return Builder<Invoice> */
    private static function openInvoices(): Builder
    {
        return Invoice::query()
            ->where('invoice_type', InvoiceType::Invoice)
            ->where('invoice_status_id', InvoiceStatus::Sent)
            ->where('is_archived', false)
            ->whereHas('amount', fn (Builder $query): Builder => $query->where('invoice_balance', '>', 0))
            ->with(['client', 'amount'])
            ->orderByDesc('invoice_date_created');
    }

    private static function invoiceOptionLabel(Invoice $invoice): string
    {
        $number = $invoice->invoice_number ?: 'Rechnung #'.$invoice->getKey();
        $client = $invoice->client?->client_name ?: 'Kein Kunde';
        $balance = number_format((float) ($invoice->amount?->invoice_balance ?? 0), 2, ',', '.').' € offen';

        return "{$number} · {$client} · {$balance}";
    }
}
