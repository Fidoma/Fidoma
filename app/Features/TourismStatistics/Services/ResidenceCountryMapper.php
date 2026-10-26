<?php

namespace App\Features\TourismStatistics\Services;

final class ResidenceCountryMapper
{
    /** @var array<string, string> */
    private const DIRECT_COUNTRIES = [
        'AU' => 'australia', 'BE' => 'belgium', 'BR' => 'brazil', 'BG' => 'bulgaria',
        'DK' => 'denmark', 'DE' => 'germany', 'EE' => 'estonia', 'FI' => 'finland',
        'FR' => 'france', 'GR' => 'greece', 'GB' => 'united_kingdom', 'UK' => 'united_kingdom', 'IN' => 'india',
        'IE' => 'ireland', 'IS' => 'iceland', 'IL' => 'israel', 'IT' => 'italy',
        'JP' => 'japan', 'CA' => 'canada', 'HR' => 'croatia', 'LV' => 'latvia',
        'LT' => 'lithuania', 'LU' => 'luxembourg', 'MT' => 'malta', 'NL' => 'netherlands',
        'NO' => 'norway', 'AT' => 'austria', 'PL' => 'poland', 'PT' => 'portugal',
        'ZA' => 'south_africa', 'RO' => 'romania', 'RU' => 'russian_federation', 'SE' => 'sweden',
        'CH' => 'switzerland', 'SK' => 'slovakia', 'SI' => 'slovenia', 'ES' => 'spain',
        'KR' => 'south_korea', 'TW' => 'taiwan', 'CZ' => 'czechia', 'TR' => 'turkey',
        'US' => 'united_states', 'UA' => 'ukraine', 'HU' => 'hungary', 'CN' => 'china_hong_kong',
        'HK' => 'china_hong_kong', 'CY' => 'cyprus',
    ];

    /** @var array<string, string> */
    private const NAME_ALIASES = [
        'DEUTSCHLAND' => 'DE', 'GERMANY' => 'DE',
        'ÖSTERREICH' => 'AT', 'OESTERREICH' => 'AT', 'AUSTRIA' => 'AT',
        'SCHWEIZ' => 'CH', 'SWITZERLAND' => 'CH',
        'VEREINIGTES KÖNIGREICH' => 'GB', 'GROSSBRITANNIEN' => 'GB', 'UNITED KINGDOM' => 'GB',
        'USA' => 'US', 'VEREINIGTE STAATEN' => 'US', 'UNITED STATES' => 'US', 'UNITED STATES OF AMERICA' => 'US',
        'NIEDERLANDE' => 'NL', 'NETHERLANDS' => 'NL',
        'FRANKREICH' => 'FR', 'FRANCE' => 'FR',
        'ITALIEN' => 'IT', 'ITALY' => 'IT',
        'SPANIEN' => 'ES', 'SPAIN' => 'ES',
        'TÜRKEI' => 'TR', 'TUERKEI' => 'TR', 'TURKEY' => 'TR',
    ];

    /** @var array<string, list<string>> */
    private const GROUPS = [
        'gulf_states' => ['BH', 'IQ', 'QA', 'KW', 'OM', 'SA', 'AE'],
        'central_america_caribbean' => [
            'VI', 'AI', 'AG', 'AW', 'BS', 'BB', 'BZ', 'BQ', 'VG', 'CR', 'CW', 'DM', 'DO',
            'SV', 'GD', 'GP', 'GT', 'HT', 'HN', 'JM', 'KY', 'CU', 'MQ', 'MX', 'MS', 'NI',
            'PA', 'PR', 'BL', 'KN', 'LC', 'MF', 'VC', 'SX', 'TT', 'TC',
        ],
        'new_zealand_oceania' => [
            'AS', 'AQ', 'CK', 'FJ', 'FM', 'PF', 'TF', 'GU', 'HM', 'UM', 'KI', 'CC', 'MH',
            'NR', 'NC', 'NZ', 'NF', 'NU', 'MP', 'PW', 'PG', 'PN', 'SB', 'WS', 'GS', 'TK',
            'TO', 'TV', 'VU', 'WF', 'CX',
        ],
        'other_north_america' => ['BM', 'GL', 'PM'],
        'other_south_america' => ['AR', 'BO', 'CL', 'EC', 'FK', 'GF', 'GY', 'CO', 'PY', 'PE', 'SR', 'UY', 'VE'],
        'other_europe' => ['AL', 'AD', 'BY', 'BA', 'FO', 'GI', 'GG', 'IM', 'JE', 'XK', 'MD', 'MC', 'ME', 'MK', 'SM', 'RS', 'SJ', 'VA', 'AX'],
        'other_africa' => [
            'EG', 'DZ', 'AO', 'GQ', 'ET', 'BJ', 'BW', 'BF', 'BI', 'CV', 'CI', 'CD', 'DJ',
            'ER', 'SZ', 'GA', 'GM', 'GH', 'GN', 'GW', 'CM', 'KE', 'KM', 'CG', 'LS', 'LR',
            'LY', 'MG', 'MW', 'ML', 'MA', 'MR', 'MU', 'YT', 'MZ', 'NA', 'NE', 'NG', 'RE',
            'RW', 'ZM', 'ST', 'SN', 'SC', 'SL', 'ZW', 'SO', 'SH', 'SS', 'SD', 'TZ', 'TG',
            'TD', 'TN', 'UG', 'EH', 'CF',
        ],
        'other_asia' => [
            'AF', 'AM', 'AZ', 'BD', 'BT', 'BN', 'GE', 'ID', 'IR', 'YE', 'JO', 'KH', 'KZ',
            'KG', 'LA', 'LB', 'MO', 'MY', 'MV', 'MN', 'MM', 'NP', 'KP', 'PK', 'PS', 'PH',
            'SG', 'LK', 'SY', 'TJ', 'TH', 'TL', 'TM', 'UZ', 'VN',
        ],
    ];

    public function countryKey(?string $residence): string
    {
        $normalized = mb_strtoupper(trim((string) $residence));

        if ($normalized === '') {
            return 'not_specified';
        }

        $isoCode = self::NAME_ALIASES[$normalized] ?? $normalized;

        if (isset(self::DIRECT_COUNTRIES[$isoCode])) {
            return self::DIRECT_COUNTRIES[$isoCode];
        }

        foreach (self::GROUPS as $key => $isoCodes) {
            if (in_array($isoCode, $isoCodes, true)) {
                return $key;
            }
        }

        return 'not_specified';
    }
}
