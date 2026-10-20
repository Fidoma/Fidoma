<x-filament-panels::page>
    @php($invoice = $this->selectedInvoice)
    @php($attempt = $this->activeAttempt)
    @php($terminal = $this->selectedTerminal)

    <div class="terminal-workspace">
        <x-filament::section class="terminal-invoices-panel">
            <x-slot name="heading">{{ __('terminals::messages.invoices.heading') }}</x-slot>
            <x-slot name="description">{{ __('terminals::messages.invoices.description') }}</x-slot>
            <x-slot name="afterHeader">
                <x-filament::badge color="gray">{{ $this->invoices->count() }} {{ __('terminals::messages.invoices.open') }}</x-filament::badge>
            </x-slot>

            <div class="terminal-invoice-filters">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input wire:model.live.debounce.300ms="search" :placeholder="__('terminals::messages.invoices.search')" />
                </x-filament::input.wrapper>
                <div class="terminal-filter-row">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="invoiceFilter" aria-label="{{ __('terminals::messages.invoices.all') }}">
                            <option value="all">{{ __('terminals::messages.invoices.all') }}</option>
                            <option value="open">{{ __('terminals::messages.invoices.open') }}</option>
                            <option value="partial">{{ __('terminals::messages.invoices.partial') }}</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="sort" aria-label="{{ __('terminals::messages.invoices.newest') }}">
                            <option value="date_desc">{{ __('terminals::messages.invoices.newest') }}</option>
                            <option value="date_asc">{{ __('terminals::messages.invoices.oldest') }}</option>
                            <option value="amount_desc">{{ __('terminals::messages.invoices.amount_desc') }}</option>
                            <option value="amount_asc">{{ __('terminals::messages.invoices.amount_asc') }}</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div class="terminal-invoice-list">
                @forelse ($this->invoices as $listedInvoice)
                    @php($partial = (float) $listedInvoice->amount->invoice_paid > 0)
                    <button type="button" wire:key="terminal-invoice-{{ $listedInvoice->getKey() }}" wire:click="selectInvoice({{ $listedInvoice->getKey() }})" @class(['terminal-invoice-item', 'is-selected' => $selectedInvoiceId === $listedInvoice->getKey()])>
                        <span class="terminal-invoice-item__content">
                            <strong>{{ $listedInvoice->invoice_number }}</strong>
                            <span>{{ $listedInvoice->client?->client_company ?: $listedInvoice->client?->client_name ?: '–' }}</span>
                            <x-filament::badge :color="$partial ? 'warning' : 'info'" size="sm">
                                {{ $partial ? __('terminals::messages.invoices.partial') : __('terminals::messages.invoices.open') }}
                            </x-filament::badge>
                        </span>
                        <span class="terminal-invoice-item__amount">
                            <strong>{{ number_format((float) $listedInvoice->amount->invoice_balance, 2, ',', '.') }} €</strong>
                            <span>{{ $listedInvoice->invoice_date_created?->format('d.m.Y') }}</span>
                        </span>
                    </button>
                @empty
                    <div class="terminal-empty-state">
                        <x-filament::icon icon="heroicon-o-document-check" />
                        <strong>{{ __('terminals::messages.invoices.empty') }}</strong>
                        <span>{{ __('terminals::messages.invoices.empty_description') }}</span>
                    </div>
                @endforelse
            </div>
        </x-filament::section>

        <div class="terminal-payment-panel">
            @if (config('terminal.mode') === 'simulator')
                <x-filament::section compact icon="heroicon-o-beaker" class="terminal-demo-section">
                    <div class="terminal-demo-copy">
                        <strong>{{ __('terminals::messages.demo.heading') }}</strong>
                        <span>{{ __('terminals::messages.demo.description') }}</span>
                    </div>
                </x-filament::section>
            @endif

            @if ($invoice)
                <x-filament::section>
                    <x-slot name="heading">{{ __('terminals::messages.payment.invoice_information') }}</x-slot>
                    <x-slot name="afterHeader">
                        <x-filament::badge :color="(float) $invoice->amount->invoice_paid > 0 ? 'warning' : 'info'">
                            {{ (float) $invoice->amount->invoice_paid > 0 ? __('terminals::messages.invoices.partial') : __('terminals::messages.invoices.open') }}
                        </x-filament::badge>
                    </x-slot>

                    <div class="terminal-selected-invoice">
                        <div class="terminal-selected-invoice__identity">
                            <strong>{{ $invoice->invoice_number }}</strong>
                            <span>{{ $invoice->client?->client_company ?: $invoice->client?->client_name ?: '–' }}</span>
                        </div>
                        <div class="terminal-summary-row"><span>{{ __('terminals::messages.payment.total') }}</span><strong>{{ number_format((float) $invoice->amount->invoice_total, 2, ',', '.') }} €</strong></div>
                        <div class="terminal-summary-row"><span>{{ __('terminals::messages.payment.paid') }}</span><strong>{{ number_format((float) $invoice->amount->invoice_paid, 2, ',', '.') }} €</strong></div>
                        <div class="terminal-balance"><span>{{ __('terminals::messages.payment.balance') }}</span><strong>{{ number_format((float) $invoice->amount->invoice_balance, 2, ',', '.') }} €</strong></div>
                    </div>
                </x-filament::section>

                <x-filament::section icon="heroicon-o-credit-card">
                    <x-slot name="heading">{{ __('terminals::messages.payment.terminal_and_amount') }}</x-slot>
                    <x-slot name="afterHeader">
                        <x-filament::badge :color="$attempt?->status->color() ?? 'success'">{{ $attempt?->status->label() ?? __('terminals::messages.payment_status.ready') }}</x-filament::badge>
                    </x-slot>

                    <form wire:submit="startPayment" class="terminal-payment-form">
                        <div class="terminal-field">
                            <label for="terminal-select">{{ __('terminals::messages.payment.select_terminal') }}</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select id="terminal-select" wire:model.live="selectedTerminalId" :disabled="$attempt && ($attempt->status->isActive() || $attempt->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Unknown)">
                                    <option value="">{{ __('terminals::messages.payment.no_terminal') }}</option>
                                    @foreach ($this->terminals as $availableTerminal)
                                        <option value="{{ $availableTerminal->getKey() }}">{{ $availableTerminal->name }} · {{ trim($availableTerminal->manufacturer.' '.$availableTerminal->model) }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            @error('selectedTerminalId') <p class="terminal-error">{{ $message }}</p> @enderror
                        </div>

                        @if ($terminal)
                            <div class="terminal-device-card">
                                <div>
                                    <strong>{{ $terminal->manufacturer }} {{ $terminal->model }} · {{ $terminal->name }}</strong>
                                    <span>{{ __('terminals::messages.payment.protocol_hint', ['host' => $terminal->host, 'port' => $terminal->port, 'protocol' => strtoupper($terminal->protocol)]) }}</span>
                                </div>
                                <x-filament::badge :color="$terminal->connection_status->color()">{{ $terminal->connection_status->label() }}</x-filament::badge>
                            </div>
                        @endif

                        <div class="terminal-field terminal-amount-field">
                            <label for="terminal-amount">{{ __('terminals::messages.payment.amount') }} (€)</label>
                            <x-filament::input.wrapper suffix="€">
                                <x-filament::input id="terminal-amount" type="number" min="0.01" step="0.01" wire:model="amount" :disabled="(float) $invoice->amount->invoice_balance <= 0 || ($attempt && ($attempt->status->isActive() || $attempt->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Unknown))" />
                            </x-filament::input.wrapper>
                            @error('amount') <p class="terminal-error">{{ $message }}</p> @enderror
                        </div>

                        <x-filament::button type="submit" size="lg" icon="heroicon-m-arrow-up-right" class="terminal-pay-button" :disabled="(float) $invoice->amount->invoice_balance <= 0 || ($attempt && ($attempt->status->isActive() || $attempt->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Unknown)) || !$selectedTerminalId">
                            {{ __('terminals::messages.payment.start') }}
                        </x-filament::button>
                    </form>

                    <div @if($attempt?->status->isActive() && $automaticStatusChecks < 40) wire:poll.3s="refreshPaymentStatus" @endif class="terminal-payment-feedback">
                        <x-filament::icon :icon="$attempt?->status->isActive() ? 'heroicon-o-arrow-path' : ($attempt?->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Successful ? 'heroicon-o-check-circle' : 'heroicon-o-signal')" />
                        <div>
                            <strong>{{ $attempt?->status->label() ?? __('terminals::messages.payment.terminal_ready') }}</strong>
                            @if ($attempt?->status_message)<span>{{ $attempt->status_message }}</span>@endif
                        </div>
                        @if ($attempt && ($attempt->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Unknown || $attempt->status->isActive()))
                            <x-filament::button type="button" color="gray" size="sm" wire:click="refreshPaymentStatus">{{ __('terminals::messages.payment.check_status') }}</x-filament::button>
                        @endif
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">{{ __('terminals::messages.history.heading') }}</x-slot>
                    <div class="terminal-history">
                        @forelse ($this->history as $historyItem)
                            <div class="terminal-history-item">
                                <span>{{ $historyItem->started_at->format('d.m.Y H:i') }}</span>
                                <strong>{{ number_format((float) $historyItem->amount, 2, ',', '.') }} €</strong>
                                <span>{{ $historyItem->terminal->name }}</span>
                                <x-filament::badge :color="$historyItem->status->color()">{{ $historyItem->status->label() }}</x-filament::badge>
                                <code>{{ $historyItem->gateway_reference ?: '–' }}</code>
                            </div>
                        @empty
                            <div class="terminal-history-empty"><x-filament::icon icon="heroicon-o-clock" /><span>{{ __('terminals::messages.history.empty') }}</span></div>
                        @endforelse
                    </div>
                </x-filament::section>
            @else
                <x-filament::section>
                    <div class="terminal-empty-state terminal-empty-state--large">
                        <x-filament::icon icon="heroicon-o-cursor-arrow-rays" />
                        <strong>{{ __('terminals::messages.payment.select_invoice') }}</strong>
                        <span>{{ __('terminals::messages.payment.select_invoice_description') }}</span>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
