<?php

namespace App\Features\Clients\Support;

use Collator;
use ResourceBundle;

final class ClientCountryOptions
{
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    /** @return array<string, string> */
    public static function all(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return self::$cache[$locale] ??= self::load($locale);
    }

    /** @return array<string, string> */
    public static function withCurrent(?string $current, ?string $locale = null): array
    {
        $options = self::all($locale);
        $current = trim((string) $current);

        if ($current === '' || array_key_exists($current, $options)) {
            return $options;
        }

        $isoCode = mb_strtoupper($current);
        $label = $options[$isoCode] ?? $current;

        return [$current => $label, ...$options];
    }

    /** @return array<string, string> */
    private static function load(string $locale): array
    {
        $countries = ResourceBundle::create($locale, 'ICUDATA-region')?->get('Countries');

        if (! $countries instanceof ResourceBundle) {
            return [];
        }

        $options = [];

        foreach ($countries as $code => $name) {
            if (preg_match('/^[A-Z]{2}$/', (string) $code) !== 1
                || in_array($code, ['EU', 'EZ', 'QO', 'UN', 'ZZ'], true)) {
                continue;
            }

            $options[(string) $code] = (string) $name;
        }

        (new Collator($locale))->asort($options);

        return $options;
    }
}
