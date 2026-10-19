<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\Projects\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LogicException;

final class CreateInvoice
{
    public function __construct(
        private readonly InvoiceDefaults $defaults,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Invoice
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('An authenticated user is required to create an invoice.');
        }

        $invoiceGroupId = $this->defaults->invoiceGroupId();

        if ($invoiceGroupId === null) {
            throw new LogicException('No number group is configured for invoices.');
        }

        $projectId = $attributes['project_id'] ?? null;

        if ($projectId !== null && ! Project::query()
            ->whereKey($projectId)
            ->where('client_id', $attributes['client_id'] ?? null)
            ->exists()) {
            throw new LogicException('The selected project must belong to the invoice client.');
        }

        $invoiceNumber = trim((string) ($attributes['invoice_number'] ?? ''));

        return Invoice::query()->create([
            ...$attributes,
            'invoice_type' => InvoiceType::Invoice,
            'creditinvoice_parent_id' => null,
            'user_id' => $userId,
            'invoice_group_id' => $invoiceGroupId,
            'invoice_status_id' => InvoiceStatus::Draft,
            'invoice_number' => $invoiceNumber !== '' ? $invoiceNumber : null,
            'invoice_time_created' => now()->format('H:i:s'),
            'invoice_url_key' => Str::random(32),
            'is_read_only' => false,
            'payment_method' => $attributes['payment_method'] ?? 0,
            'invoice_terms' => $attributes['invoice_terms'] ?? '',
            'invoice_einvoicing_version' => $attributes['invoice_einvoicing_version'] ?? '',
        ]);
    }
}
