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
            'Vertragsparteien' => [
                '{{text:vermieter_name}}' => 'Text',
                '{{text:vermieter_strasse}}' => 'Text',
                '{{text:vermieter_plz}}' => 'Text',
                '{{text:vermieter_ort}}' => 'Text',
                '{{text:vermieter_telefon}}' => 'Text',
                '{{text:mieter_name}}' => 'Text',
                '{{text:mieter_strasse}}' => 'Text',
                '{{text:mieter_plz}}' => 'Text',
                '{{text:mieter_ort}}' => 'Text',
                '{{text:mitbewohner_name}}' => 'Text',
            ],
            'Mietobjekt (§1)' => [
                '{{text:objekt_adresse}}' => 'Text',
                '{{number:wohnflaeche}}' => 'Zahl',
                '{{number:zimmer}}' => 'Zahl',
                '{{number:keller}}' => 'Zahl',
                '{{number:terrasse}}' => 'Zahl',
                '{{number:kueche}}' => 'Zahl',
                '{{number:garage}}' => 'Zahl',
                '{{number:balkon}}' => 'Zahl',
                '{{number:bad}}' => 'Zahl',
                '{{number:wc}}' => 'Zahl',
                '{{number:stellplatz}}' => 'Zahl',
            ],
            'Mietkosten (§2–§5)' => [
                '{{amount:kaltmiete}}' => 'Betrag',
                '{{amount:nebenkosten}}' => 'Betrag',
                '{{amount:gesamtmiete}}' => 'Berechnet',
                '{{text:vermieter_iban}}' => 'Text',
                '{{amount:kaution}}' => 'Betrag',
                '{{select:kaution_zahlungsart}}' => 'Auswahl',
                '{{amount:kleinreparatur_grenze}}' => 'Betrag',
            ],
            'Vertragslaufzeit und Unterschrift' => [
                '{{date:vertragsbeginn}}' => 'Datum',
                '{{date:vertragsende}}' => 'Datum',
                '{{text:vertragsort}}' => 'Text',
                '{{date:vertragsdatum}}' => 'Datum',
            ],
        ];
    }
}
