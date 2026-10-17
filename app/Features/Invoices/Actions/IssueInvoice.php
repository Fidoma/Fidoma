<?php

namespace App\Features\Invoices\Actions;

use App\Features\InvoiceGroups\Actions\GenerateInvoiceGroupNumber;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use Illuminate\Support\Facades\DB;
use LogicException;

final class IssueInvoice
{
    public function __construct(
        private readonly GenerateInvoiceGroupNumber $generateNumber,
    ) {}

    public function handle(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            if ($invoice->invoice_status_id !== InvoiceStatus::Draft) {
                return $invoice;
            }

            if (! $invoice->invoice_group_id) {
                throw new LogicException('No number group is configured for this invoice.');
            }

            $invoice->update([
                'invoice_number' => $this->generateNumber->handle($invoice->invoice_group_id),
                'invoice_status_id' => InvoiceStatus::Open,
                'is_read_only' => true,
            ]);

            return $invoice->refresh();
        });
    }
}
