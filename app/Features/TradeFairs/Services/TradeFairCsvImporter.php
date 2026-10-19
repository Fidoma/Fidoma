<?php

namespace App\Features\TradeFairs\Services;

use App\Features\TradeFairs\Models\TradeFair;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class TradeFairCsvImporter
{
    private const HEADERS = ['Startdatum', 'Enddatum', 'Name', 'Slogan', 'Ort', 'Land', 'Webseite'];

    /**
     * @param  callable(int, string): void|null  $reportError
     * @return array{created: int, updated: int, unchanged: int, failed: int}
     */
    public function import(string $path, ?callable $reportError = null): array
    {
        $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("CSV-Datei kann nicht gelesen werden: {$path}");
        }

        try {
            $headers = null;
            $line = 0;

            while (($values = fgetcsv($handle, null, ';', '"', '')) !== false) {
                $line++;
                $values = array_map($this->normalizeEncoding(...), $values);

                if ($this->emptyRow($values)) {
                    continue;
                }

                if ($headers === null) {
                    $headers = array_map(fn (string $value): string => trim(preg_replace('/^\xEF\xBB\xBF/', '', $value)), $values);

                    if ($headers !== self::HEADERS) {
                        throw new RuntimeException('Die CSV-Kopfzeile entspricht nicht dem erwarteten Format.');
                    }

                    continue;
                }

                try {
                    if (count($values) !== count(self::HEADERS)) {
                        throw new RuntimeException('Falsche Anzahl von Spalten.');
                    }

                    /** @var array<string, string> $row */
                    $row = array_combine(self::HEADERS, $values);
                    $outcome = $this->persist($this->normalize($row));
                    $result[$outcome]++;
                } catch (Throwable $exception) {
                    $result['failed']++;
                    if ($reportError !== null) {
                        $reportError($line, $exception->getMessage());
                    }
                }
            }

            if ($headers === null) {
                throw new RuntimeException('Die CSV-Datei ist leer.');
            }
        } finally {
            fclose($handle);
        }

        return $result;
    }

    /** @param array<string, string> $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        $name = $this->nullable($row['Name']);

        if ($name === null) {
            throw new RuntimeException('Der Veranstaltungsname fehlt.');
        }

        $start = $this->date($row['Startdatum'], 'Startdatum');
        $end = $this->date($row['Enddatum'], 'Enddatum');

        if ($end->isBefore($start)) {
            throw new RuntimeException('Das Enddatum liegt vor dem Startdatum.');
        }

        $location = $this->nullable($row['Ort']);
        $country = $this->nullable($row['Land']);

        return [
            'identity_key' => hash('sha256', implode('|', [
                Str::lower(Str::squish($name)),
                Str::lower(Str::squish($location ?? '')),
                Str::lower(Str::squish($country ?? '')),
                $start->year,
            ])),
            'name' => $name,
            'description' => $this->nullable($row['Slogan']),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'location' => $location,
            'country' => $country,
            'website' => $this->website($row['Webseite']),
            'is_local' => $this->classifyLocation($location),
            'is_active' => true,
            'source' => 'messe-stuttgart-csv',
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function persist(array $attributes): string
    {
        return DB::transaction(function () use ($attributes): string {
            $fair = TradeFair::query()->where('identity_key', $attributes['identity_key'])->lockForUpdate()->first();

            if ($fair === null) {
                $year = CarbonImmutable::parse($attributes['start_date'])->year;
                $sameNameCandidates = TradeFair::query()
                    ->where('source', $attributes['source'])
                    ->whereBetween('start_date', ["{$year}-01-01", "{$year}-12-31"])
                    ->lockForUpdate()
                    ->get()
                    ->filter(fn (TradeFair $candidate): bool => $this->normalizedIdentityPart($candidate->name)
                        === $this->normalizedIdentityPart($attributes['name']));

                if ($sameNameCandidates->count() > 1) {
                    throw new RuntimeException('Mehrdeutige Zuordnung: mehrere Veranstaltungen mit gleichem Namen und Jahr gefunden.');
                }

                if ($sameNameCandidates->count() === 1) {
                    $fair = $sameNameCandidates->firstOrFail();
                }
            }

            if ($fair === null) {
                TradeFair::query()->create($attributes);

                return 'created';
            }

            // Preserve manual locality and activation corrections on later imports.
            unset($attributes['is_local'], $attributes['is_active']);
            $fair->fill($attributes);

            if (! $fair->isDirty()) {
                return 'unchanged';
            }

            $fair->save();

            return 'updated';
        });
    }

    private function date(string $value, string $field): CarbonImmutable
    {
        $value = Str::squish($value);
        $date = CarbonImmutable::createFromFormat('!d. m. Y', $value, 'Europe/Berlin');

        if ($date === false || $date->format('d. m. Y') !== $value) {
            throw new RuntimeException("Ungültiges {$field}: {$value}");
        }

        return $date;
    }

    private function website(string $value): ?string
    {
        $website = $this->nullable($value);

        if ($website === null) {
            return null;
        }

        if (! preg_match('~^[a-z][a-z0-9+.-]*://~i', $website)) {
            $website = 'https://'.$website;
        }

        if (filter_var($website, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException("Ungültige Webseite: {$website}");
        }

        return $website;
    }

    private function classifyLocation(?string $location): ?bool
    {
        if ($location === null) {
            return null;
        }

        $normalized = Str::lower(Str::ascii($location));

        if (str_contains($normalized, 'stuttgart') || str_contains($normalized, 'messepiazza') || str_contains($normalized, '70629')) {
            return true;
        }

        return false;
    }

    private function nullable(string $value): ?string
    {
        $value = Str::squish($value);

        return $value === '' ? null : $value;
    }

    private function normalizedIdentityPart(string $value): string
    {
        return Str::lower(Str::squish($value));
    }

    private function normalizeEncoding(mixed $value): string
    {
        $value = (string) $value;

        return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    /** @param list<string> $values */
    private function emptyRow(array $values): bool
    {
        return collect($values)->every(fn (string $value): bool => trim($value) === '');
    }
}
