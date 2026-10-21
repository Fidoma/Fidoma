<x-filament-panels::page>
    @php($invoice = $this->selectedInvoice)
    @php($attempt = $this->activeAttempt)
    @php($blockingAttempt = $this->blockingAttempt)
    @php($terminalBlocked = $blockingAttempt !== null && $blockingAttempt?->getKey() !== $attempt?->getKey())

    <div class="terminal-workspace">
        <x-filament::section class="terminal-invoices-panel">
            <x-slot name="afterHeader">
                <div class="terminal-invoices-header-actions">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input wire:model.live.debounce.300ms="search" :placeholder="__('terminals::messages.invoices.search')" />
                    </x-filament::input.wrapper>
                    <x-filament::badge color="gray">{{ $this->invoices->count() }} {{ __('terminals::messages.invoices.open') }}</x-filament::badge>
                </div>
            </x-slot>

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

                <x-filament::section>
                    <x-slot name="afterHeader">
                        <x-filament::badge :color="$attempt?->status->color() ?? (($terminalAvailable && ! $terminalBlocked) ? 'success' : 'danger')">
                            {{ $attempt?->status->label() ?? (($terminalAvailable && ! $terminalBlocked) ? __('terminals::messages.payment_status.ready') : __('terminals::messages.payment_status.unavailable')) }}
                        </x-filament::badge>
                    </x-slot>

                    <div class="terminal-payment-feedback">
                        <x-filament::icon :icon="($terminalAvailable && ! $terminalBlocked) ? 'heroicon-o-signal' : 'heroicon-o-exclamation-triangle'" />
                        <div>
                            <strong>{{ $terminalSimulated ? __('terminals::messages.payment.simulator_ready') : __('terminals::messages.payment.connection_status', ['status' => $terminalHealthStatus]) }}</strong>
                            @if ($terminalBlocked)
                                <span>{{ __('terminals::messages.errors.blocked_by_attempt', ['time' => $blockingAttempt->started_at?->format('d.m.Y H:i:s') ?? '–']) }}</span>
                            @elseif ($terminalHealthMessage)
                                <span>{{ $terminalHealthMessage }}</span>
                            @endif
                        </div>
                        <x-filament::button type="button" color="gray" size="sm" wire:click="refreshTerminalHealth" wire:loading.attr="disabled" wire:target="refreshTerminalHealth">
                            {{ __('terminals::messages.payment.check_connection') }}
                        </x-filament::button>
                    </div>

                    <form wire:submit="startPayment" class="terminal-payment-form">
                        <div class="terminal-field terminal-amount-field">
                            <label for="terminal-amount">{{ __('terminals::messages.payment.amount') }} (€)</label>
                            <x-filament::input.wrapper suffix="€">
                                <x-filament::input id="terminal-amount" type="number" min="0.01" step="0.01" wire:model="amount" :disabled="! $terminalAvailable || $terminalBlocked || (float) $invoice->amount->invoice_balance <= 0 || ($attempt && ($attempt->status->isActive() || $attempt->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Unknown))" />
                            </x-filament::input.wrapper>
                            @error('amount') <p class="terminal-error">{{ $message }}</p> @enderror
                        </div>

                        <x-filament::button type="submit" size="lg" icon="heroicon-m-arrow-up-right" class="terminal-pay-button" wire:loading.attr="disabled" wire:target="startPayment" :disabled="! $terminalAvailable || $terminalBlocked || (float) $invoice->amount->invoice_balance <= 0 || ($attempt && ($attempt->status->isActive() || $attempt->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Unknown))">
                            <span wire:loading.remove wire:target="startPayment">{{ __('terminals::messages.payment.start') }}</span>
                            <span wire:loading wire:target="startPayment">{{ __('terminals::messages.payment.starting') }}</span>
                        </x-filament::button>
                    </form>
                    @error('payment') <p class="terminal-error">{{ $message }}</p> @enderror

                    <div @if($attempt?->status->isActive() && $automaticStatusChecks < 40) wire:poll.3s="refreshPaymentStatus" @endif class="terminal-payment-feedback">
                        <x-filament::icon :icon="$attempt?->status->isActive() ? 'heroicon-o-arrow-path' : ($attempt?->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Successful ? 'heroicon-o-check-circle' : 'heroicon-o-signal')" />
                        <div>
                            <strong>{{ $attempt?->status->label() ?? __('terminals::messages.payment.terminal_ready') }}</strong>
                            @if ($attempt?->status_message)<span>{{ $attempt->status_message }}</span>@endif
                        </div>
                        @if ($attempt?->status === \App\Features\Terminals\Enums\TerminalPaymentStatus::Unknown)
                            <x-filament::button type="button" color="gray" size="sm" wire:click="refreshPaymentStatus">
                                {{ __('terminals::messages.payment.check_status') }}
                            </x-filament::button>
                        @endif
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

    <div class="terminal-global-history">
        @livewire(\App\Features\Terminals\Livewire\TerminalPaymentAttemptsTable::class)
    </div>
</x-filament-panels::page>
