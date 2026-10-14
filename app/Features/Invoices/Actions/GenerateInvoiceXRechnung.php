<?php

namespace App\Features\Invoices\Actions;

use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Services\InvoiceDocumentData;
use DomainException;
use horstoeko\invoicesuite\InvoiceSuiteSettings;
use horstoeko\zugferd\codelists\ZugferdCurrencyCodes;
use horstoeko\zugferd\codelists\ZugferdElectronicAddressScheme;
use horstoeko\zugferd\codelists\ZugferdInvoiceType;
use horstoeko\zugferd\codelists\ZugferdUnitCodes;
use horstoeko\zugferd\codelists\ZugferdVatCategoryCodes;
use horstoeko\zugferd\codelists\ZugferdVatTypeCodes;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdProfiles;

final class GenerateInvoiceXRechnung
{
    public function __construct(private readonly InvoiceDocumentData $documentData) {}

    public function handle(Invoice $invoice): string
    {
        $invoice = $this->documentData->prepare($invoice);
        $this->validate($invoice);

        $seller = $invoice->user;
        $buyer = $invoice->client;
        // Avoid scanning every Composer dependency when InvoiceSuite discovers its own providers.
        InvoiceSuiteSettings::setDiscoveryNamespaces(['horstoeko\\invoicesuite']);
        $builder = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_XRECHNUNG_3);
        $builder->setDocumentInformation(
            $invoice->invoice_number,
            match ($invoice->invoice_type) {
                InvoiceType::Invoice => ZugferdInvoiceType::INVOICE,
                InvoiceType::Credit => ZugferdInvoiceType::CREDITNOTE,
                InvoiceType::Cancellation => ZugferdInvoiceType::CORRECTION,
            },
            $invoice->invoice_date_created,
            ZugferdCurrencyCodes::EURO,
        );
        $builder->setDocumentBuyerReference($buyer->client_einvoicing_reference);

        if ($invoice->parentInvoice) {
            $builder->setDocumentInvoiceReferencedDocument(
                $invoice->parentInvoice->invoice_number,
                issueDate: $invoice->parentInvoice->invoice_date_created,
            );
        }

        $sellerName = $seller->company_name ?: $seller->name;
        $builder->setDocumentSeller($sellerName, (string) $seller->getKey());
        $builder->setDocumentSellerAddress(
            $seller->address_line_1,
            $seller->address_line_2,
            null,
            $seller->postal_code,
            $seller->city,
            $this->countryCode($seller->country),
            $seller->state,
        );
        $builder->setDocumentSellerContact(
            $seller->invoicing_contact ?: $seller->name,
            null,
            $seller->phone,
            $seller->fax,
            $seller->email,
        );
        $builder->setDocumentSellerCommunication(ZugferdElectronicAddressScheme::UNECE3155_EM, $seller->email);

        if ($seller->vat_id) {
            $builder->addDocumentSellerVATRegistrationNumber($seller->vat_id);
        } else {
            $builder->addDocumentSellerTaxNumber($seller->tax_number);
        }

        $buyerName = $buyer->client_company ?: trim($buyer->client_name.' '.$buyer->client_surname);
        $builder->setDocumentBuyer($buyerName, (string) $buyer->getKey());
        $builder->setDocumentBuyerAddress(
            $buyer->client_address_1,
            $buyer->client_address_2,
            null,
            $buyer->client_zip,
            $buyer->client_city,
            $this->countryCode($buyer->client_country),
            $buyer->client_state,
        );
        $builder->setDocumentBuyerCommunication(ZugferdElectronicAddressScheme::UNECE3155_EM, $buyer->client_email);

        if ($buyer->client_vat_id) {
            $builder->addDocumentBuyerVATRegistrationNumber($buyer->client_vat_id);
        } elseif ($buyer->client_tax_code) {
            $builder->addDocumentBuyerTaxNumber($buyer->client_tax_code);
        }

        $builder->addDocumentPaymentTerm(
            $invoice->invoice_terms ?: 'Zahlbar bis zum Fälligkeitsdatum.',
            $invoice->invoice_date_due,
        );

        if ($seller->iban) {
            $builder->addDocumentPaymentMeanToCreditTransfer(
                preg_replace('/\s+/', '', $seller->iban),
                $sellerName,
                payeeBic: $seller->bic,
                paymentReference: $invoice->invoice_number,
            );
        }

        $taxes = [];
        $lineTotal = 0.0;

        foreach ($invoice->items as $index => $item) {
            $quantity = abs((float) $item->item_quantity);
            $price = abs((float) $item->item_price);
            $netAmount = abs((float) $item->amount->item_subtotal);
            $taxAmount = abs((float) $item->amount->item_tax_total);
            $taxPercent = (float) ($item->taxRate?->tax_rate_percent ?? 0);
            $category = $taxPercent > 0
                ? ZugferdVatCategoryCodes::STAN_RATE
                : ZugferdVatCategoryCodes::ZERO_RATE_GOOD;

            $builder->addNewPosition((string) ($index + 1));
            $builder->setDocumentPositionProductDetails(
                $item->item_name,
                $item->item_description ?: null,
                $item->product?->product_number,
            );
            $builder->setDocumentPositionNetPrice($price);
            $builder->setDocumentPositionQuantity($quantity, $this->unitCode($item->item_product_unit));
            $builder->addDocumentPositionTax($category, ZugferdVatTypeCodes::VALUE_ADDED_TAX, $taxPercent);
            $builder->setDocumentPositionLineSummation($netAmount);

            $key = $category.'-'.$taxPercent;
            $taxes[$key] ??= ['category' => $category, 'percent' => $taxPercent, 'base' => 0.0, 'tax' => 0.0];
            $taxes[$key]['base'] += $netAmount;
            $taxes[$key]['tax'] += $taxAmount;
            $lineTotal += $netAmount;
        }

        foreach ($taxes as $tax) {
            $builder->addDocumentTax(
                $tax['category'],
                ZugferdVatTypeCodes::VALUE_ADDED_TAX,
                round($tax['base'], 2),
                round($tax['tax'], 2),
                $tax['percent'],
            );
        }

        $taxTotal = array_sum(array_column($taxes, 'tax'));
        $grandTotal = round($lineTotal + $taxTotal, 2);
        $builder->setDocumentSummation(
            $grandTotal,
            abs((float) $invoice->amount->invoice_balance),
            round($lineTotal, 2),
            0.0,
            0.0,
            round($lineTotal, 2),
            round($taxTotal, 2),
            totalPrepaidAmount: abs((float) $invoice->amount->invoice_paid),
        );

        return $builder->getContent();
    }

    public function filename(Invoice $invoice): string
    {
        $number = preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number ?: (string) $invoice->getKey());

        return 'XRechnung-'.$number.'.xml';
    }

    private function validate(Invoice $invoice): void
    {
        $missing = [];
        $seller = $invoice->user;
        $buyer = $invoice->client;

        foreach ([
            'Rechnungsnummer' => $invoice->invoice_number,
            'Absendername oder Firmenname' => $seller?->company_name ?: $seller?->name,
            'Absenderstraße' => $seller?->address_line_1,
            'Absender-PLZ' => $seller?->postal_code,
            'Absenderort' => $seller?->city,
            'Absenderland' => $seller?->country,
            'Absendertelefon' => $seller?->phone,
            'Absender-E-Mail' => $seller?->email,
            'USt-IdNr. oder Steuernummer des Absenders' => $seller?->vat_id ?: $seller?->tax_number,
            'Kundenname oder Firma' => $buyer?->client_company ?: $buyer?->client_name,
            'Kundenstraße' => $buyer?->client_address_1,
            'Kunden-PLZ' => $buyer?->client_zip,
            'Kundenort' => $buyer?->client_city,
            'Kundenland' => $buyer?->client_country,
            'Kunden-E-Mail' => $buyer?->client_email,
            'Käuferreferenz / Leitweg-ID' => $buyer?->client_einvoicing_reference,
        ] as $label => $value) {
            if (blank($value)) {
                $missing[] = $label;
            }
        }

        if ($invoice->items->isEmpty()) {
            $missing[] = 'mindestens eine Rechnungsposition';
        }

        if (! $invoice->amount) {
            $missing[] = 'berechnete Rechnungssummen';
        }

        if ($invoice->items->contains(fn ($item): bool => ! $item->amount)) {
            $missing[] = 'berechnete Positionssummen';
        }

        if ($missing !== []) {
            throw new DomainException('Für die XRechnung fehlen: '.implode(', ', $missing).'.');
        }

        if ((float) $invoice->invoice_discount_amount !== 0.0 || (float) $invoice->invoice_discount_percent !== 0.0) {
            throw new DomainException('Rechnungsrabatte werden im ersten XRechnung-Ausbauschritt noch nicht unterstützt.');
        }

        if ($invoice->items->contains(fn ($item): bool => (float) $item->item_discount_amount !== 0.0 || $item->item_tax_included)) {
            throw new DomainException('Positionsrabatte und Bruttopreise werden im ersten XRechnung-Ausbauschritt noch nicht unterstützt.');
        }

        if ($invoice->invoiceTaxRates->isNotEmpty()) {
            throw new DomainException('Zusätzliche Rechnungssteuern werden im ersten XRechnung-Ausbauschritt noch nicht unterstützt.');
        }

        $this->countryCode($seller->country);
        $this->countryCode($buyer->client_country);
    }

    private function countryCode(?string $country): string
    {
        $normalized = mb_strtolower(trim((string) $country));
        $codes = [
            'de' => 'DE', 'deutschland' => 'DE', 'germany' => 'DE',
            'at' => 'AT', 'österreich' => 'AT', 'austria' => 'AT',
            'ch' => 'CH', 'schweiz' => 'CH', 'switzerland' => 'CH',
            'fr' => 'FR', 'frankreich' => 'FR', 'france' => 'FR',
            'it' => 'IT', 'italien' => 'IT', 'italy' => 'IT',
            'nl' => 'NL', 'niederlande' => 'NL', 'netherlands' => 'NL',
            'be' => 'BE', 'belgien' => 'BE', 'belgium' => 'BE',
            'xk' => 'XK', 'kosovo' => 'XK',
        ];

        if (isset($codes[$normalized])) {
            return $codes[$normalized];
        }

        if (preg_match('/^[a-z]{2}$/', $normalized) === 1) {
            return strtoupper($normalized);
        }

        throw new DomainException("Das Land '{$country}' muss als ISO-Ländercode (z. B. DE) gespeichert sein.");
    }

    private function unitCode(?string $unit): string
    {
        return match (mb_strtolower(trim((string) $unit))) {
            'kg', 'kilogramm', 'kilogram' => ZugferdUnitCodes::REC20_KILOGRAM,
            'm', 'meter', 'metre' => ZugferdUnitCodes::REC20_METRE,
            'h', 'std', 'stunde', 'stunden', 'hour', 'hours' => ZugferdUnitCodes::REC20_HOUR,
            'tag', 'tage', 'day', 'days' => ZugferdUnitCodes::REC20_DAY,
            default => ZugferdUnitCodes::REC20_PIECE,
        };
    }
}
