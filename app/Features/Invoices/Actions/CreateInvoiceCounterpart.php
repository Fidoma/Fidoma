<?php

namespace App\Features\Invoices\Actions;

use App\Features\InvoiceGroups\Actions\GenerateInvoiceGroupNumber;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDefaults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class CreateInvoiceCounterpart
{
    public function __construct(
        private readonly GenerateInvoiceGroupNumber $generateNumber,
        private readonly InvoiceDefaults $defaults,
        private readonly RecalculateInvoice $recalculate,
    ) {}

    public function handle(Invoice $source, InvoiceType $type): Invoice
    {
        if ($type === InvoiceType::Invoice) {
            throw new LogicException('A counterpart must be a credit or cancellation invoice.');
        }

        if ($source->invoice_type !== InvoiceType::Invoice) {
            throw new LogicException('Only a regular invoice can be used as the source document.');
        }

        if (! in_array($source->invoice_status_id, [InvoiceStatus::Sent, InvoiceStatus::Paid], true)) {
            throw new LogicException('Only an issued invoice can be credited or cancelled.');
        }

        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('An authenticated user is required to create an invoice.');
        }

        return DB::transaction(function () use ($source, $type, $userId): Invoice {
            $source = Invoice::query()->lockForUpdate()->with(['items', 'invoiceTaxRates'])->findOrFail($source->getKey());
            $invoiceGroupId = $this->defaults->invoiceGroupIdFor($type);

            if ($invoiceGroupId === null) {
                throw new LogicException("No number group is configured for {$type->label()}.");
            }

            $target = Invoice::query()->create([
                'invoice_type' => $type,
                'creditinvoice_parent_id' => $source->getKey(),
                'user_id' => $userId,
                'client_id' => $source->client_id,
                'invoice_group_id' => $invoiceGroupId,
                'invoice_status_id' => InvoiceStatus::Draft,
                'is_read_only' => false,
                'invoice_password' => '',
                'invoice_date_created' => today(),
                'invoice_time_created' => now()->format('H:i:s'),
                'invoice_date_due' => today()->addDays($this->defaults->dueDays()),
                'invoice_number' => $this->generateNumber->handle($invoiceGroupId),
                'invoice_discount_amount' => -abs((float) $source->invoice_discount_amount),
                'invoice_discount_percent' => $source->invoice_discount_percent,
                'invoice_terms' => $source->invoice_terms,
                'invoice_url_key' => Str::random(32),
                'payment_method' => $source->payment_method,
                'invoice_einvoicing_version' => $source->invoice_einvoicing_version,
            ]);

            foreach ($source->items as $item) {
                $target->items()->create([
                    'item_tax_rate_id' => $item->item_tax_rate_id,
                    'item_tax_included' => $item->item_tax_included,
                    'item_product_id' => $item->item_product_id,
                    'item_date_added' => today(),
                    'item_task_id' => null,
                    'item_name' => $item->item_name,
                    'item_description' => $item->item_description,
                    'item_quantity' => -abs((float) $item->item_quantity),
                    'item_price' => $item->item_price,
                    'item_discount_amount' => $item->item_discount_amount,
                    'item_order' => $item->item_order,
                    'item_is_recurring' => 0,
                    'item_product_unit' => $item->item_product_unit,
                    'item_product_unit_id' => $item->item_product_unit_id,
                    'item_date' => $item->item_date,
                ]);
            }

            foreach ($source->invoiceTaxRates as $taxRate) {
                $target->invoiceTaxRates()->create([
                    'tax_rate_id' => $taxRate->tax_rate_id,
                    'include_item_tax' => $taxRate->include_item_tax,
                    'invoice_tax_rate_amount' => 0,
                ]);
            }

            $source->updateQuietly(['is_read_only' => true]);

            return $this->recalculate->handle($target);
        });
    }
}
