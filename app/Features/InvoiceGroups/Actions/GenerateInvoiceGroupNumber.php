<?php

namespace App\Features\InvoiceGroups\Actions;

use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\InvoiceGroups\Services\InvoiceGroupNumberFormatter;
use Illuminate\Support\Facades\DB;

final class GenerateInvoiceGroupNumber
{
    public function __construct(
        private readonly InvoiceGroupNumberFormatter $formatter,
    ) {}

    public function handle(int $invoiceGroupId): string
    {
        return DB::transaction(function () use ($invoiceGroupId): string {
            $invoiceGroup = InvoiceGroup::query()
                ->lockForUpdate()
                ->findOrFail($invoiceGroupId);

            $number = $this->formatter->format(
                $invoiceGroup->invoice_group_identifier_format,
                $invoiceGroup->invoice_group_next_id,
                $invoiceGroup->invoice_group_left_pad,
            );

            $invoiceGroup->increment('invoice_group_next_id');

            return $number;
        });
    }
}
