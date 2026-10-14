<?php

namespace App\Features\EmailTemplates\Services;

use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDocumentData;

final class InvoiceEmailTemplateRenderer
{
    public function __construct(private readonly InvoiceDocumentData $documentData) {}

    public function render(Invoice $invoice, ?string $content, bool $escapeValues = false): string
    {
        $invoice = $this->documentData->prepare($invoice);
        $client = $invoice->client;
        $amount = $invoice->amount;
        $user = $invoice->user;

        $values = [
            'client_name' => $client?->client_name,
            'client_company' => $client?->client_company,
            'client_email' => $client?->client_email,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date_created' => $invoice->invoice_date_created?->format('d.m.Y'),
            'invoice_date_due' => $invoice->invoice_date_due?->format('d.m.Y'),
            'invoice_item_subtotal' => $this->money($amount?->invoice_item_subtotal),
            'invoice_item_tax_total' => $this->money($amount?->invoice_item_tax_total),
            'invoice_total' => $this->money($amount?->invoice_total),
            'invoice_paid' => $this->money($amount?->invoice_paid),
            'invoice_balance' => $this->money($amount?->invoice_balance),
            'invoice_terms' => $invoice->invoice_terms,
            'invoice_type' => $invoice->invoice_type->label(),
            'user_name' => $user?->name,
            'user_company' => $user?->company_name,
            'user_email' => $user?->email,
            'user_phone' => $user?->phone,
            'user_web' => $user?->website,
            'user_vat_id' => $user?->vat_id,
            'user_tax_code' => $user?->tax_number,
            'user_bank' => $user?->bank_name,
            'user_iban' => $user?->iban,
            'user_bic' => $user?->bic,
        ];

        $replacements = [];
        foreach ($values as $key => $value) {
            $replacement = (string) ($value ?? '');
            $replacements['{{{'.$key.'}}}'] = $escapeValues
                ? e($replacement)
                : $replacement;
        }

        return strtr((string) $content, $replacements);
    }

    /** @return list<string> */
    public static function placeholders(): array
    {
        return [
            'client_name', 'client_company', 'client_email', 'invoice_number', 'invoice_type',
            'invoice_date_created', 'invoice_date_due', 'invoice_item_subtotal',
            'invoice_item_tax_total', 'invoice_total', 'invoice_paid', 'invoice_balance',
            'invoice_terms', 'user_name', 'user_company', 'user_email', 'user_phone',
            'user_web', 'user_vat_id', 'user_tax_code', 'user_bank', 'user_iban', 'user_bic',
        ];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.').' €';
    }
}
