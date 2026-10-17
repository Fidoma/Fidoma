<?php

namespace App\Features\Contracts\Support;

final class ContractPlaceholders
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'Kundendaten' => [
                '{{text:client_number}}' => 'Text',
                '{{text:client_company}}' => 'Text',
                '{{text:client_name}}' => 'Text',
                '{{text:client_address}}' => 'Text',
                '{{text:client_city}}' => 'Text',
                '{{text:client_email}}' => 'Text',
            ],
            'Ausstellerdaten' => [
                '{{text:issuer_company}}' => 'Text',
                '{{text:issuer_name}}' => 'Text',
                '{{text:issuer_address}}' => 'Text',
                '{{text:issuer_city}}' => 'Text',
                '{{text:issuer_email}}' => 'Text',
                '{{text:issuer_vat_id}}' => 'Text',
                '{{text:issuer_iban}}' => 'Text',
            ],
            'Vertragsdaten' => [
                '{{date:contract_create_date}}' => 'Datum',
                '{{date:contract_start_date}}' => 'Datum',
                '{{date:contract_end_date}}' => 'Datum',
                '{{text:contract_location}}' => 'Text',
            ],
        ];
    }
}
