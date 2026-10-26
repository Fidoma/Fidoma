<?php

namespace App\Features\TourismStatistics\Support;

final class ResidenceCountryCatalog
{
    /**
     * The order and export headings are copied from the official HOTE import sample.
     *
     * @return list<array{key: string, label: string, arrivals_heading: string, overnight_heading: string, code: int}>
     */
    public static function all(): array
    {
        return [
            self::country('gulf_states', 'Arabische Golfstaaten', 'ANK_Golfstaaten', 'UEB_Golfstaaten', 60),
            self::country('australia', 'Australien', 'ANK_Australien', 'UEB_Australien', 75),
            self::country('belgium', 'Belgien', 'ANK_Belgien', 'UEB_Belgien', 21),
            self::country('brazil', 'Brasilien', 'ANK_Brasilien', 'UEB_Brasilien', 73),
            self::country('bulgaria', 'Bulgarien', 'ANK_Bulgarien', 'UEB_Bulgarien', 47),
            self::country('denmark', 'Dänemark', 'ANK_Daenemark', 'UEB_Daenemark', 22),
            self::country('germany', 'Deutschland', 'ANK_Deutschland', 'UEB_Deutschland', 13),
            self::country('estonia', 'Estland', 'ANK_Estland', 'UEB_Estland', 15),
            self::country('finland', 'Finnland', 'ANK_Finnland', 'UEB_Finnland', 23),
            self::country('france', 'Frankreich', 'ANK_Frankreich', 'UEB_Frankreich', 24),
            self::country('greece', 'Griechenland', 'ANK_Griechenland', 'UEB_Griechenland', 25),
            self::country('united_kingdom', 'Vereinigtes Königreich', 'ANK_VereinigtesKoenigreich', 'UEB_VereinigtesKoenigreich', 26),
            self::country('india', 'Indien', 'ANK_Indien', 'UEB_Indien', 69),
            self::country('ireland', 'Irland', 'ANK_Irland', 'UEB_Irland', 27),
            self::country('iceland', 'Island', 'ANK_Island', 'UEB_Island', 28),
            self::country('israel', 'Israel', 'ANK_Israel', 'UEB_Israel', 62),
            self::country('italy', 'Italien', 'ANK_Italien', 'UEB_Italien', 29),
            self::country('japan', 'Japan', 'ANK_Japan', 'UEB_Japan', 63),
            self::country('canada', 'Kanada', 'ANK_Kanada', 'UEB_Kanada', 70),
            self::country('croatia', 'Kroatien', 'ANK_Kroatien', 'UEB_Kroatien', 20),
            self::country('latvia', 'Lettland', 'ANK_Lettland', 'UEB_Lettland', 16),
            self::country('lithuania', 'Litauen', 'ANK_Litauen', 'UEB_Litauen', 17),
            self::country('luxembourg', 'Luxemburg', 'ANK_Luxemburg', 'UEB_Luxemburg', 30),
            self::country('malta', 'Malta', 'ANK_Malta', 'UEB_Malta', 18),
            self::country('central_america_caribbean', 'Mittelamerika und Karibik', 'ANK_Mittelamerika_Karibik', 'UEB_Mittelamerika_Karibik', 72),
            self::country('new_zealand_oceania', 'Neuseeland und Ozeanien', 'ANK_Neuseeland_Ozeanien', 'UEB_NeuseelandOzeanien', 79),
            self::country('netherlands', 'Niederlande', 'ANK_Niederlande', 'UEB_Niederlande', 31),
            self::country('norway', 'Norwegen', 'ANK_Norwegen', 'UEB_Norwegen', 32),
            self::country('austria', 'Österreich', 'ANK_Oestereich', 'UEB_Oestereich', 33),
            self::country('not_specified', 'Ohne Angabe', 'ANK_OhneAngabe', 'UEB_OhneAngabe', 90),
            self::country('poland', 'Polen', 'ANK_Polen', 'UEB_Polen', 34),
            self::country('portugal', 'Portugal', 'ANK_Portugal', 'UEB_Portugal', 35),
            self::country('south_africa', 'Südafrika', 'ANK_Suedafrika', 'UEB_Suedafrika', 50),
            self::country('romania', 'Rumänien', 'ANK_Rumaenien', 'UEB_Rumaenien', 48),
            self::country('russian_federation', 'Russische Föderation', 'ANK_RussischeFoederation', 'UEB_RussischeFoederation', 36),
            self::country('sweden', 'Schweden', 'ANK_Schweden', 'UEB_Schweden', 37),
            self::country('switzerland', 'Schweiz', 'ANK_Schweiz', 'UEB_Schweiz', 38),
            self::country('slovakia', 'Slowakei', 'ANK_Slowakei', 'UEB_Slowakei', 19),
            self::country('slovenia', 'Slowenien', 'ANK_Slowenien', 'UEB_Slowenien', 46),
            self::country('other_africa', 'Sonstige afrikanische Länder', 'ANK_Sonst.Afrikanische_Laender', 'UEB_Sonst.AfrikanischeLaender', 55),
            self::country('other_asia', 'Sonstige asiatische Länder', 'ANK_Sonst.AsiatischeLaender', 'UEB_Sonst.AsiatischeLaender', 66),
            self::country('other_europe', 'Sonstige europäische Länder', 'ANK_Sonst.Europ.Laender', 'UEB_Sonst.Europ.Laender', 43),
            self::country('other_north_america', 'Sonstiges Nordamerika', 'ANK_Sonst.Nordamerika', 'UEB_Sonst.Nordamerika', 76),
            self::country('other_south_america', 'Sonstige südamerikanische Länder', 'ANK_Sonst.Suedamerikan.Laender', 'UEB_Sonst.Suedamerikan.Laender', 74),
            self::country('spain', 'Spanien', 'ANK_Spanien', 'UEB_Spanien', 39),
            self::country('south_korea', 'Südkorea', 'ANK_Suedkorea', 'UEB_Suedkorea', 64),
            self::country('taiwan', 'Taiwan', 'ANK_Taiwan', 'UEB_Taiwan', 65),
            self::country('czechia', 'Tschechien', 'ANK_Tschechien', 'UEB_Tschechien', 40),
            self::country('turkey', 'Türkei', 'ANK_Tuerkei', 'UEB_Tuerkei', 41),
            self::country('united_states', 'Vereinigte Staaten', 'ANK_VereinigteStaaten', 'UEB_Vereinigte Staaten', 71),
            self::country('ukraine', 'Ukraine', 'ANK_Ukraine', 'UEB_Ukraine', 44),
            self::country('hungary', 'Ungarn', 'ANK_Ungarn', 'UEB_Ungarn', 42),
            self::country('china_hong_kong', 'China einschließlich Hongkong', 'ANK_ChinaHongkong', 'UEB_ChinaHongkong', 61),
            self::country('cyprus', 'Zypern', 'ANK_Zypern', 'UEB_Zypern', 45),
        ];
    }

    /** @return list<array{country_key: string, arrivals: null, overnight_stays: null}> */
    public static function emptyRows(): array
    {
        return array_map(fn (array $country): array => [
            'country_key' => $country['key'],
            'arrivals' => null,
            'overnight_stays' => null,
        ], self::all());
    }

    /** @return list<array{country_key: string, arrivals: null, overnight_stays: null}> */
    public static function emptyRowsByCode(): array
    {
        return array_map(fn (array $country): array => [
            'country_key' => $country['key'],
            'arrivals' => null,
            'overnight_stays' => null,
        ], self::sortedByCode());
    }

    /** @return list<array{key: string, label: string, arrivals_heading: string, overnight_heading: string, code: int}> */
    public static function sortedByCode(): array
    {
        $countries = self::all();
        usort($countries, fn (array $left, array $right): int => $left['code'] <=> $right['code']);

        return $countries;
    }

    public static function label(string $key): string
    {
        return self::find($key)['label'] ?? $key;
    }

    public static function maximum(string $key): int
    {
        return $key === 'germany' ? 999999 : 99999;
    }

    /** @return array{key: string, label: string, arrivals_heading: string, overnight_heading: string, code: int}|null */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $country) {
            if ($country['key'] === $key) {
                return $country;
            }
        }

        return null;
    }

    /** @return array{key: string, label: string, arrivals_heading: string, overnight_heading: string, code: int} */
    private static function country(
        string $key,
        string $label,
        string $arrivalsHeading,
        string $overnightHeading,
        int $code,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'arrivals_heading' => $arrivalsHeading,
            'overnight_heading' => $overnightHeading,
            'code' => $code,
        ];
    }
}
