<?php

namespace App\Features\Invoices\Filament\Resources\Invoices\Tables;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\ArchiveInvoice;
use App\Features\Invoices\Actions\CreateInvoiceCounterpart;
use App\Features\Invoices\Actions\RecordInvoicePayment;
use App\Features\Invoices\Enums\InvoiceBillingType;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Actions\InvoiceEmailAction;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Projects\Models\Project;
use App\Features\Settings\Services\ModuleSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('invoice_date_created', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('invoices::messages.fields.invoice'))
                    ->badge()
                    ->color(fn (Invoice $record): string => $record->invoice_type->color())
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('client.client_name')
                    ->label(__('invoices::messages.fields.client'))
                    ->url(fn (Invoice $record): string => ClientResource::getUrl('view', ['record' => $record->client_id]))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('project.project_number')
                    ->label(__('invoices::messages.fields.project_short'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn (): bool => app(ModuleSettings::class)->isEnabled('projects')),
                TextColumn::make('billing_type')
                    ->label(__('invoices::messages.fields.billing_type'))
                    ->badge()
                    ->formatStateUsing(fn (InvoiceBillingType $state): string => $state->label())
                    ->color(fn (InvoiceBillingType $state): string => $state->color())
                    ->toggleable(),
                TextColumn::make('invoice_date_created')->label(__('invoices::messages.fields.date'))->date('d.m.Y')->sortable()->toggleable(),
                TextColumn::make('invoice_date_due')->label(__('invoices::messages.fields.due_date'))->date('d.m.Y')->sortable()
                    ->color(fn (Invoice $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->toggleable(),
                TextColumn::make('invoice_status_id')->label(__('invoices::messages.fields.status'))->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->color())
                    ->toggleable(),
                TextColumn::make('amount.invoice_total')->label(__('invoices::messages.fields.total'))->money('EUR', locale: app()->getLocale())->sortable()->toggleable(),
                TextColumn::make('amount.invoice_paid')->label(__('invoices::messages.fields.paid'))->money('EUR', locale: app()->getLocale())->toggleable(),
                TextColumn::make('amount.invoice_balance')->label(__('invoices::messages.fields.outstanding'))->money('EUR', locale: app()->getLocale())->sortable()->toggleable(),
            ])
            ->filters([
                Filter::make('invoice_number')
                    ->label(__('invoices::messages.fields.invoice_number'))
                    ->schema([
                        TextInput::make('value')->label(__('invoices::messages.fields.invoice_number')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('invoice_number', 'like', '%'.trim((string) $data['value']).'%')
                        : $query),
                SelectFilter::make('client_id')
                    ->label(__('invoices::messages.fields.client'))
                    ->options(fn (): array => Client::query()
                        ->orderBy('client_name')
                        ->pluck('client_name', 'client_id')
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project_id')
                    ->label(__('invoices::messages.fields.project_short'))
                    ->options(fn (): array => Project::query()
                        ->orderBy('project_number')
                        ->get()
                        ->mapWithKeys(fn (Project $project): array => [
                            $project->getKey() => collect([$project->project_number, $project->project_name])
                                ->filter()
                                ->implode(' · '),
                        ])->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => app(ModuleSettings::class)->isEnabled('projects')),
                SelectFilter::make('billing_type')
                    ->label(__('invoices::messages.fields.billing_type'))
                    ->options(collect(InvoiceBillingType::cases())->mapWithKeys(
                        fn (InvoiceBillingType $type): array => [$type->value => $type->label()],
                    )->all()),
                self::dateRangeFilter('invoice_date_created', __('invoices::messages.fields.invoice_date')),
                self::dateRangeFilter('invoice_date_due', __('invoices::messages.fields.due_date_filter')),
                self::amountRangeFilter('invoice_total', __('invoices::messages.fields.total_amount'), 'invoice_total'),
                self::amountRangeFilter('invoice_paid', __('invoices::messages.fields.paid'), 'invoice_paid'),
                self::amountRangeFilter('invoice_balance', __('invoices::messages.fields.outstanding'), 'invoice_balance'),
            ])
            ->recordUrl(null)
            ->recordAction('viewDetails')
            ->toolbarActions([
                CreateAction::make()
                    ->label(__('invoices::messages.singular'))
                    ->icon(Heroicon::Plus)
                    ->url(InvoiceResource::getUrl('create')),
            ])
            ->recordActions([
                Action::make('viewDetails')
                    ->label(__('invoices::messages.details.open'))
                    ->icon(Heroicon::OutlinedEye)
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalHeading(fn (Invoice $record): string => $record->invoice_number ?: __('invoices::messages.statuses.draft'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('invoices::messages.details.close'))
                    ->modalContent(fn (Invoice $record) => view('invoices::invoice-details-modal', [
                        'invoiceId' => $record->getKey(),
                    ])),
                ActionGroup::make([
                    Action::make('viewPdf')
                        ->label(__('invoices::messages.actions.view_pdf'))
                        ->icon(Heroicon::OutlinedDocumentArrowDown)
                        ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record]))
                        ->openUrlInNewTab(),
                    Action::make('downloadXRechnung')
                        ->label(__('invoices::messages.actions.download_xrechnung'))
                        ->icon(Heroicon::OutlinedCodeBracketSquare)
                        ->url(fn (Invoice $record): string => route('invoices.xrechnung', ['invoice' => $record]))
                        ->openUrlInNewTab(),
                    InvoiceEmailAction::make(),
                    self::recordPaymentAction(),
                    self::counterpartAction('createCredit', InvoiceType::Credit),
                    self::counterpartAction('createCancellation', InvoiceType::Cancellation),
                    DeleteAction::make()
                        ->label(__('invoices::messages.actions.delete_draft'))
                        ->visible(fn (Invoice $record): bool => $record->invoice_status_id === InvoiceStatus::Draft),
                    Action::make('archive')
                        ->label(__('invoices::messages.actions.archive'))
                        ->icon(Heroicon::OutlinedArchiveBox)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('invoices::messages.archive.heading'))
                        ->modalDescription(__('invoices::messages.archive.description'))
                        ->modalSubmitActionLabel(__('invoices::messages.actions.archive'))
                        ->visible(fn (Invoice $record): bool => ! $record->is_archived
                            && $record->invoice_status_id !== InvoiceStatus::Draft)
                        ->action(function (Invoice $record): void {
                            app(ArchiveInvoice::class)->handle($record);

                            Notification::make()
                                ->success()
                                ->title(__('invoices::messages.archive.success'))
                                ->send();
                        }),
                ])
                    ->label(__('invoices::messages.actions.options'))
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->tooltip(__('invoices::messages.actions.options'))
                    ->iconButton(),
            ]);
    }

    private static function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label(__('invoices::messages.actions.record_payment'))
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('primary')
            ->visible(fn (Invoice $record): bool => $record->invoice_type === InvoiceType::Invoice
                && ! $record->is_archived
                && $record->invoice_status_id !== InvoiceStatus::Paid
                && (float) ($record->amount?->invoice_balance ?? 0) > 0)
            ->modalHeading(fn (Invoice $record): string => __('invoices::messages.payment.heading', ['number' => $record->invoice_number]))
            ->modalSubmitActionLabel(__('invoices::messages.actions.save_payment'))
            ->schema([
                DatePicker::make('payment_date')
                    ->label(__('invoices::messages.fields.payment_date'))
                    ->default(now()->toDateString())
                    ->required(),
                Select::make('payment_method_id')
                    ->label(__('invoices::messages.fields.payment_method'))
                    ->options(fn () => PaymentMethod::query()
                        ->orderBy('payment_method_name')
                        ->pluck('payment_method_name', 'payment_method_id'))
                    ->default(fn (Invoice $record): ?int => $record->payment_method
                        ?: (app(InvoiceDefaults::class)->paymentMethodId() ?: null))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('payment_amount')
                    ->label(__('invoices::messages.fields.amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(fn (Invoice $record): float => (float) ($record->amount?->invoice_balance ?? 0))
                    ->default(fn (Invoice $record): float => (float) ($record->amount?->invoice_balance ?? 0))
                    ->suffix('€')
                    ->required(),
                TextInput::make('payment_note')
                    ->label(__('invoices::messages.fields.note'))
                    ->default('')
                    ->maxLength(1000),
            ])
            ->action(function (Invoice $record, array $data): void {
                app(RecordInvoicePayment::class)->handle($record, $data);

                Notification::make()
                    ->success()
                    ->title(__('invoices::messages.payment.success'))
                    ->send();
            });
    }

    private static function counterpartAction(string $name, InvoiceType $type): Action
    {
        return Action::make($name)
            ->label($type === InvoiceType::Credit ? __('invoices::messages.actions.create_credit') : __('invoices::messages.actions.cancel_invoice'))
            ->icon($type === InvoiceType::Credit ? Heroicon::OutlinedDocumentMinus : Heroicon::OutlinedNoSymbol)
            ->color($type === InvoiceType::Credit ? 'success' : 'danger')
            ->visible(fn (Invoice $record): bool => $record->invoice_type === InvoiceType::Invoice
                && ! $record->is_archived)
            ->disabled(fn (Invoice $record): bool => $record->invoice_status_id === InvoiceStatus::Draft)
            ->tooltip(fn (Invoice $record): ?string => $record->invoice_status_id === InvoiceStatus::Draft
                ? __('invoices::messages.counterpart.must_issue')
                : null)
            ->requiresConfirmation()
            ->modalHeading($type === InvoiceType::Credit ? __('invoices::messages.actions.create_credit') : __('invoices::messages.actions.cancel_invoice'))
            ->modalDescription(fn (Invoice $record): string => __('invoices::messages.counterpart.description', [
                'type' => $type->label(),
                'number' => $record->invoice_number,
            ]))
            ->action(function (Action $action, Invoice $record) use ($type): void {
                $counterpart = app(CreateInvoiceCounterpart::class)->handle($record, $type);

                Notification::make()
                    ->success()
                    ->title(__('invoices::messages.counterpart.success', ['type' => $type->label()]))
                    ->send();

                $action->redirect(InvoiceResource::getUrl('edit', ['record' => $counterpart]));
            });
    }

    private static function dateRangeFilter(string $name, string $label): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->schema([
                DatePicker::make('from')->label(__('invoices::messages.fields.from'))->native(false),
                DatePicker::make('until')->label(__('invoices::messages.fields.until'))->native(false),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($name, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($name, '<=', $date)));
    }

    private static function amountRangeFilter(string $name, string $label, string $column): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->schema([
                TextInput::make('from')->label(__('invoices::messages.fields.from'))->numeric()->prefix('€'),
                TextInput::make('until')->label(__('invoices::messages.fields.until'))->numeric()->prefix('€'),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when(
                    filled($data['from'] ?? null),
                    fn (Builder $query): Builder => $query->whereHas(
                        'amount',
                        fn (Builder $query): Builder => $query->where($column, '>=', (float) $data['from']),
                    ),
                )
                ->when(
                    filled($data['until'] ?? null),
                    fn (Builder $query): Builder => $query->whereHas(
                        'amount',
                        fn (Builder $query): Builder => $query->where($column, '<=', (float) $data['until']),
                    ),
                ));
    }
}
