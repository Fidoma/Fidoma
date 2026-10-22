<?php

namespace App\Features\BankTransactions\Filament\Resources\BankTransactions\Schemas;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\Documents\Services\PaperlessClient;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Payments\Filament\Resources\Payments\PaymentResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

final class BankTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('bank-transactions::messages.detail.overview'))
                    ->columns(['default' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('amount')
                            ->label(__('bank-transactions::messages.fields.amount'))
                            ->money(fn (BankTransaction $record): string => $record->currency, locale: app()->getLocale())
                            ->color(fn (BankTransaction $record): string => (float) $record->amount < 0 ? 'danger' : 'success')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large),
                        TextEntry::make('counterparty_name')
                            ->label(__('bank-transactions::messages.fields.counterparty'))
                            ->placeholder('–')
                            ->weight(FontWeight::SemiBold),
                        TextEntry::make('booking_date')
                            ->label(__('bank-transactions::messages.fields.booking_date'))
                            ->date('d.m.Y'),
                        TextEntry::make('assignment_status')
                            ->label(__('bank-transactions::messages.fields.assignment_status'))
                            ->state(fn (BankTransaction $record): string => $record->statusLabel())
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Zugeordnet' => 'success',
                                'Vorgemerkt' => 'warning',
                                default => 'gray',
                            }),
                    ]),
                Section::make(__('bank-transactions::messages.detail.payment_information'))
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextEntry::make('purpose')
                            ->label(__('bank-transactions::messages.fields.purpose'))
                            ->placeholder('–')
                            ->columnSpanFull(),
                        TextEntry::make('counterparty_iban')
                            ->label(__('bank-transactions::messages.fields.counterparty_iban'))
                            ->copyable()
                            ->placeholder('–'),
                        TextEntry::make('account_iban')
                            ->label(__('bank-transactions::messages.fields.bank_account'))
                            ->copyable()
                            ->placeholder('–'),
                        TextEntry::make('booking_date')
                            ->label(__('bank-transactions::messages.fields.booking_date'))
                            ->date('d.m.Y'),
                        TextEntry::make('value_date')
                            ->label(__('bank-transactions::messages.fields.value_date'))
                            ->date('d.m.Y')
                            ->placeholder('–'),
                        TextEntry::make('booking_text')
                            ->label(__('bank-transactions::messages.fields.booking_text'))
                            ->placeholder('–')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('bank-transactions::messages.detail.linked_records'))
                    ->columns(1)
                    ->schema([
                        TextEntry::make('unassigned_notice')
                            ->hidden(fn (BankTransaction $record): bool => $record->mappings->isNotEmpty())
                            ->label('')
                            ->state(__('bank-transactions::messages.detail.no_linked_records'))
                            ->color('gray'),
                        RepeatableEntry::make('linked_records')
                            ->hidden(fn (BankTransaction $record): bool => $record->mappings->isEmpty())
                            ->label('')
                            ->state(fn (BankTransaction $record): array => self::linkedRecords($record))
                            ->columns(['default' => 1, 'lg' => 4])
                            ->schema([
                                TextEntry::make('type')
                                    ->label(__('bank-transactions::messages.fields.type'))
                                    ->badge()
                                    ->color(fn (string $state): string => $state === __('bank-transactions::messages.detail.document') ? 'info' : 'success'),
                                TextEntry::make('label')
                                    ->label(__('bank-transactions::messages.fields.business_record'))
                                    ->url(fn (Get $get): ?string => $get('url'))
                                    ->openUrlInNewTab(fn (Get $get): bool => (bool) $get('external'))
                                    ->weight(FontWeight::SemiBold),
                                TextEntry::make('detail')
                                    ->label(__('bank-transactions::messages.fields.details'))
                                    ->placeholder('–'),
                                TextEntry::make('amount')
                                    ->label(__('bank-transactions::messages.detail.assignment_amount'))
                                    ->money(fn (Get $get): string => $get('currency') ?: 'EUR', locale: app()->getLocale())
                                    ->placeholder('–'),
                            ]),
                        TextEntry::make('remaining_amount')
                            ->label(__('bank-transactions::messages.detail.remaining_amount'))
                            ->state(fn (BankTransaction $record): float => $record->mappings->isEmpty()
                                ? abs((float) $record->amount)
                                : 0.0)
                            ->money(fn (BankTransaction $record): string => $record->currency, locale: app()->getLocale())
                            ->weight(FontWeight::SemiBold),
                    ]),
                Section::make(__('bank-transactions::messages.detail.technical_data'))
                    ->columns(['default' => 1, 'lg' => 2])
                    ->collapsed()
                    ->schema([
                        TextEntry::make('bank_reference')->label(__('bank-transactions::messages.fields.bank_reference'))->copyable()->placeholder('–'),
                        TextEntry::make('end_to_end_id')->label(__('bank-transactions::messages.fields.end_to_end_id'))->copyable()->placeholder('–'),
                        TextEntry::make('bank_transaction_id')->label(__('bank-transactions::messages.fields.bank_transaction_id'))->copyable()->placeholder('–'),
                        TextEntry::make('transaction_reference')->label(__('bank-transactions::messages.fields.internal_reference'))->copyable()->placeholder('–'),
                        TextEntry::make('created_at')->label(__('bank-transactions::messages.fields.imported_at'))->dateTime('d.m.Y H:i')->placeholder('–'),
                        TextEntry::make('validated')
                            ->label(__('bank-transactions::messages.fields.validation'))
                            ->formatStateUsing(fn (bool $state): string => $state
                                ? __('bank-transactions::messages.status.validated')
                                : __('bank-transactions::messages.status.not_validated'))
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),
            ]);
    }

    /** @return list<array{type: string, label: string, detail: ?string, amount: ?float, currency: string, url: ?string, external: bool}> */
    private static function linkedRecords(BankTransaction $transaction): array
    {
        $documentsById = collect(app(PaperlessClient::class)->cachedDocuments())->keyBy('id');

        return $transaction->mappings
            ->flatMap(function ($mapping) use ($documentsById, $transaction): array {
                if ($mapping->document_id) {
                    $document = $documentsById->get($mapping->document_id);

                    return [[
                        'type' => __('bank-transactions::messages.detail.document'),
                        'label' => $document?->title ?: __('bank-transactions::messages.detail.paperless_document', ['id' => $mapping->document_id]),
                        'detail' => collect([$document?->supplier, $document?->invoiceNumber])->filter()->implode(' · ') ?: null,
                        'amount' => $document?->amount,
                        'currency' => $document?->currency ?: $transaction->currency,
                        'url' => app(PaperlessClient::class)->documentDetailsUrl($mapping->document_id),
                        'external' => true,
                    ]];
                }

                $payment = $mapping->payment;
                $invoice = $payment?->invoice;
                $records = [[
                    'type' => __('bank-transactions::messages.detail.payment'),
                    'label' => __('bank-transactions::messages.detail.payment_number', ['id' => $payment?->getKey() ?? $mapping->getKey()]),
                    'detail' => collect([
                        $payment?->payment_date?->format('d.m.Y'),
                        $payment?->paymentMethod?->payment_method_name,
                    ])->filter()->implode(' · ') ?: null,
                    'amount' => abs((float) $transaction->amount),
                    'currency' => $transaction->currency,
                    'url' => PaymentResource::getUrl('index', array_filter([
                        'tableSearch' => $invoice?->invoice_number,
                    ])),
                    'external' => false,
                ]];

                if (! $invoice) {
                    return $records;
                }

                $records[] = [
                    'type' => __('bank-transactions::messages.detail.invoice'),
                    'label' => $invoice->invoice_number ?: __('bank-transactions::messages.detail.invoice_number', ['id' => $invoice->getKey()]),
                    'detail' => collect([
                        $invoice?->client?->client_name,
                        __('bank-transactions::messages.detail.invoice_open_amount', [
                            'amount' => number_format((float) ($invoice->amount?->invoice_balance ?? 0), 2, ',', '.'),
                            'currency' => $transaction->currency,
                        ]),
                    ])->filter()->implode(' · ') ?: null,
                    'amount' => null,
                    'currency' => $transaction->currency,
                    'url' => InvoiceResource::getUrl('edit', ['record' => $invoice]),
                    'external' => false,
                ];

                return $records;
            })
            ->values()
            ->all();
    }
}
