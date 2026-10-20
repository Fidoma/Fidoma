<?php

namespace App\Features\Terminals\Filament\Pages;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Filament\Resources\PaymentTerminals\PaymentTerminalResource;
use App\Features\Terminals\Models\PaymentTerminal;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use App\Features\Terminals\Services\TerminalPaymentService;
use App\Filament\Concerns\HasConfigurableNavigation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

class Terminal extends Page
{
    use HasConfigurableNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'terminals';

    protected string $view = 'terminals::terminal';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $search = '';

    public string $invoiceFilter = 'all';

    public string $sort = 'date_desc';

    public ?int $selectedInvoiceId = null;

    public ?int $selectedTerminalId = null;

    public string $amount = '';

    public ?string $activeAttemptId = null;

    public int $automaticStatusChecks = 0;

    protected static function navigationModule(): string
    {
        return 'terminals';
    }

    public static function getNavigationLabel(): string
    {
        return __('terminals::messages.feature');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('terminals::messages.navigation_group');
    }

    public function getTitle(): string
    {
        return __('terminals::messages.feature');
    }

    public function getSubheading(): ?string
    {
        return __('terminals::messages.subtitle');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $this->selectedTerminalId = PaymentTerminal::query()
            ->where('user_id', auth()->id())->where('is_enabled', true)->orderBy('name')->value('id');
    }

    #[Computed]
    public function invoices(): Collection
    {
        return $this->invoiceQuery()->limit(100)->get();
    }

    #[Computed]
    public function selectedInvoice(): ?Invoice
    {
        if ($this->selectedInvoiceId === null) {
            return null;
        }

        return $this->ownedInvoiceQuery()->find($this->selectedInvoiceId);
    }

    #[Computed]
    public function terminals(): Collection
    {
        return PaymentTerminal::query()
            ->where('user_id', auth()->id())
            ->where('is_enabled', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedTerminal(): ?PaymentTerminal
    {
        if ($this->selectedTerminalId === null) {
            return null;
        }

        return PaymentTerminal::query()
            ->where('user_id', auth()->id())
            ->where('is_enabled', true)
            ->find($this->selectedTerminalId);
    }

    #[Computed]
    public function history(): Collection
    {
        if ($this->selectedInvoiceId === null) {
            return collect();
        }

        return TerminalPaymentAttempt::query()
            ->where('user_id', auth()->id())
            ->where('invoice_id', $this->selectedInvoiceId)
            ->with('terminal')
            ->latest('started_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function activeAttempt(): ?TerminalPaymentAttempt
    {
        return $this->activeAttemptId === null
            ? null
            : TerminalPaymentAttempt::query()->where('user_id', auth()->id())->find($this->activeAttemptId);
    }

    public function selectInvoice(int $invoiceId): void
    {
        $invoice = $this->invoiceQuery(applyFilters: false)->findOrFail($invoiceId);
        $this->selectedInvoiceId = $invoice->getKey();
        $this->amount = number_format((float) $invoice->amount->invoice_balance, 2, '.', '');
        $this->activeAttemptId = null;
        $this->automaticStatusChecks = 0;
        $this->resetValidation();
        unset($this->selectedInvoice, $this->history, $this->activeAttempt);
    }

    public function startPayment(TerminalPaymentService $service): void
    {
        $this->validate([
            'selectedInvoiceId' => ['required', 'integer'],
            'selectedTerminalId' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $invoice = $this->invoiceQuery(applyFilters: false)->findOrFail($this->selectedInvoiceId);
        $terminal = PaymentTerminal::query()->where('user_id', auth()->id())->findOrFail($this->selectedTerminalId);
        $attempt = $service->start($invoice, $terminal, $this->amount, auth()->user());
        $this->activeAttemptId = $attempt->getKey();
        $this->automaticStatusChecks = 0;
        $this->afterAttemptUpdate($attempt);
    }

    public function refreshPaymentStatus(TerminalPaymentService $service): void
    {
        $attempt = $this->activeAttempt;

        if ($attempt === null || $attempt->status->isFinal()) {
            return;
        }

        $this->automaticStatusChecks++;
        $this->afterAttemptUpdate($service->refresh($attempt, auth()->user()));
    }

    private function afterAttemptUpdate(TerminalPaymentAttempt $attempt): void
    {
        unset($this->activeAttempt, $this->history, $this->invoices, $this->selectedInvoice);

        if ($attempt->status === TerminalPaymentStatus::Successful) {
            Notification::make()->success()->title(__('terminals::messages.notifications.booked'))->send();
            $invoice = $this->ownedInvoiceQuery()->find($this->selectedInvoiceId);
            $this->amount = $invoice ? number_format((float) $invoice->amount->invoice_balance, 2, '.', '') : '';
        } elseif ($attempt->status === TerminalPaymentStatus::DemoSuccessful) {
            Notification::make()->warning()->title(__('terminals::messages.demo.not_booked'))->send();
        } elseif ($attempt->status === TerminalPaymentStatus::Unknown) {
            Notification::make()->warning()->title(__('terminals::messages.payment_status.unknown'))->send();
        }
    }

    /** @return Builder<Invoice> */
    private function invoiceQuery(bool $applyFilters = true): Builder
    {
        $query = $this->ownedInvoiceQuery()
            ->where('invoice_type', InvoiceType::Invoice)
            ->where('invoice_status_id', InvoiceStatus::Open)
            ->where('is_archived', false)
            ->whereHas('amount', fn (Builder $query): Builder => $query->where('invoice_balance', '>', 0))
            ->with(['client', 'amount']);

        if (! $applyFilters) {
            return $query;
        }

        $query->when(filled($this->search), function (Builder $query): void {
            $search = '%'.trim($this->search).'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('invoice_number', 'like', $search)
                    ->orWhereHas('client', fn (Builder $query): Builder => $query
                        ->where('client_name', 'like', $search)
                        ->orWhere('client_company', 'like', $search));
            });
        });

        $query->when($this->invoiceFilter === 'open', fn (Builder $query): Builder => $query->whereHas('amount', fn (Builder $query): Builder => $query->where('invoice_paid', '<=', 0)))
            ->when($this->invoiceFilter === 'partial', fn (Builder $query): Builder => $query->whereHas('amount', fn (Builder $query): Builder => $query->where('invoice_paid', '>', 0)));

        return match ($this->sort) {
            'date_asc' => $query->orderBy('invoice_date_created'),
            'amount_desc' => $query->orderByDesc(Invoice::query()->from('ip_invoice_amounts')->select('invoice_balance')->whereColumn('ip_invoice_amounts.invoice_id', 'ip_invoices.invoice_id')),
            'amount_asc' => $query->orderBy(Invoice::query()->from('ip_invoice_amounts')->select('invoice_balance')->whereColumn('ip_invoice_amounts.invoice_id', 'ip_invoices.invoice_id')),
            default => $query->orderByDesc('invoice_date_created'),
        };
    }

    /** @return Builder<Invoice> */
    private function ownedInvoiceQuery(): Builder
    {
        return Invoice::query()
            ->where('user_id', auth()->id())
            ->with(['client', 'amount']);
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageTerminals')
                ->label(__('terminals::messages.management.feature'))
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->url(PaymentTerminalResource::getUrl()),
        ];
    }
}
