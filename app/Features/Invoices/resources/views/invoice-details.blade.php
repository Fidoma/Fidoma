<div class="flex flex-col gap-6">
    <x-filament::section compact>
        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="mb-2 text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $invoice->invoice_type->label() }}
                    </div>
                    <div class="break-words text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $invoice->invoice_number ?: __('invoices::messages.statuses.draft') }}
                    </div>
                    <div class="mt-2 break-words text-sm text-gray-600 dark:text-gray-300">
                        {{ $invoice->client?->client_name ?: '–' }}
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-filament::badge :color="$invoice->invoice_status_id->color()">
                        {{ $invoice->invoice_status_id->label() }}
                    </x-filament::badge>
                    @if ($invoice->is_archived)
                        <x-filament::badge color="gray" icon="heroicon-o-archive-box">
                            {{ __('invoices::messages.tabs.archived') }}
                        </x-filament::badge>
                    @elseif ($invoice->isOverdue())
                        <x-filament::badge color="danger" icon="heroicon-o-clock">
                            {{ __('invoices::messages.tabs.overdue') }}
                        </x-filament::badge>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-4 border-t border-gray-200 pt-5 dark:border-white/10">
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('invoices::messages.fields.invoice_date') }}</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $invoice->invoice_date_created?->format('d.m.Y') ?: '–' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('invoices::messages.fields.due_date') }}</dt>
                    <dd @class(['mt-1 text-sm', 'text-danger-600 dark:text-danger-400' => $invoice->isOverdue(), 'text-gray-950 dark:text-white' => ! $invoice->isOverdue()])>
                        {{ $invoice->invoice_date_due?->format('d.m.Y') ?: '–' }}
                    </dd>
                </div>
            </dl>
        </div>
    </x-filament::section>

    <x-filament::section :heading="__('invoices::messages.details.payment_overview')" icon="heroicon-o-chart-pie" compact>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('invoices::messages.fields.total_amount') }}</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight tabular-nums text-gray-950 dark:text-white">{{ Number::currency((float) ($invoice->amount?->invoice_total ?? 0), 'EUR', app()->getLocale()) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('invoices::messages.fields.paid') }}</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight tabular-nums text-success-600 dark:text-success-400">{{ Number::currency((float) ($invoice->amount?->invoice_paid ?? 0), 'EUR', app()->getLocale()) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('invoices::messages.fields.outstanding') }}</div>
                <div @class(['mt-2 text-2xl font-semibold tracking-tight tabular-nums', 'text-danger-600 dark:text-danger-400' => $invoice->isOverdue(), 'text-gray-950 dark:text-white' => ! $invoice->isOverdue()])>{{ Number::currency((float) ($invoice->amount?->invoice_balance ?? 0), 'EUR', app()->getLocale()) }}</div>
            </div>
        </div>
        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-white/10">
            <div class="mb-2 flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-300">{{ __('invoices::messages.details.payment_progress') }}</span>
                <span class="font-medium text-gray-950 dark:text-white">{{ $this->paymentProgress() }} %</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10" role="progressbar" aria-label="{{ __('invoices::messages.details.payment_progress') }}" aria-valuenow="{{ $this->paymentProgress() }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-success-500" style="width: {{ $this->paymentProgress() }}%"></div>
            </div>
        </div>
    </x-filament::section>

    @if ($invoice->invoice_status_id !== \App\Features\Invoices\Enums\InvoiceStatus::Draft)
        <x-filament::section :heading="__('invoices::messages.details.documents_and_delivery')" icon="heroicon-o-paper-airplane" compact>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-filament::button tag="a" :href="route('invoices.pdf', ['invoice' => $invoice])" target="_blank" color="gray" icon="heroicon-o-eye">
                    {{ __('invoices::messages.actions.view_pdf') }}
                </x-filament::button>
                <x-filament::button tag="a" :href="route('invoices.pdf.download', ['invoice' => $invoice])" color="gray" icon="heroicon-o-arrow-down-tray">
                    {{ __('invoices::messages.details.download_pdf') }}
                </x-filament::button>
                <x-filament::button tag="a" :href="route('invoices.xrechnung', ['invoice' => $invoice])" color="gray" icon="heroicon-o-code-bracket-square">
                    {{ __('invoices::messages.actions.download_xrechnung') }}
                </x-filament::button>
                {{ $this->sendEmailAction }}
            </div>
        </x-filament::section>
    @endif

    <x-filament::section :heading="__('invoices::messages.details.payments')" icon="heroicon-o-banknotes" compact>
        <x-slot name="afterHeader">
            {{ $this->addPaymentAction }}
        </x-slot>
        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($invoice->payments as $payment)
                <div class="py-4 first:pt-0 last:pb-0" wire:key="invoice-payment-{{ $payment->getKey() }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-950 dark:text-white">
                                {{ $payment->paymentMethod?->payment_method_name ?: __('invoices::messages.details.payment') }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $payment->payment_date?->format('d.m.Y') }}</div>
                            @if (filled($payment->payment_note))
                                <div class="mt-2 break-words text-sm text-gray-500 dark:text-gray-400">{{ $payment->payment_note }}</div>
                            @endif
                        </div>
                        <div class="shrink-0 text-base font-semibold tabular-nums text-success-600 dark:text-success-400">
                            +{{ Number::currency((float) $payment->payment_amount, 'EUR', app()->getLocale()) }}
                        </div>
                    </div>
                    @foreach ($payment->bankTransactionMappings as $mapping)
                        @if ($mapping->bankTransaction)
                            <div class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                <x-filament::icon icon="heroicon-o-link" class="h-4 w-4" />
                                <span>{{ __('invoices::messages.details.bank_transaction_reference', ['id' => $mapping->bankTransaction->getKey()]) }}</span>
                                <span>·</span>
                                <span class="break-words">{{ $mapping->bankTransaction->counterparty_name ?: $mapping->bankTransaction->purpose }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @empty
                <div class="flex flex-col items-center gap-3 py-6 text-center">
                    <div class="rounded-full bg-gray-50 p-3 dark:bg-white/5">
                        <x-filament::icon icon="heroicon-o-banknotes" class="h-6 w-6 text-gray-400" />
                    </div>
                    <p class="max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ __('invoices::messages.details.no_payments') }}</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>

    <x-filament::section :heading="__('invoices::messages.details.more_actions')" :contained="false" compact>
        <div class="flex flex-wrap gap-3">
            @if (! $invoice->is_archived && $invoice->invoice_status_id === \App\Features\Invoices\Enums\InvoiceStatus::Draft && \App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource::canEdit($invoice))
                <x-filament::button tag="a" :href="\App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource::getUrl('edit', ['record' => $invoice])" color="gray" icon="heroicon-o-pencil-square">
                    {{ __('invoices::messages.details.edit_invoice') }}
                </x-filament::button>
            @endif
            {{ $this->createCreditAction }}
            {{ $this->archiveAction }}
            {{ $this->cancelInvoiceAction }}
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</div>
