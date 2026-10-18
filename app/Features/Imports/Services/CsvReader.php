<?php

namespace App\Features\Imports\Services;

use Generator;
use RuntimeException;

final class CsvReader
{
    /** @return array{headers: list<string>, rows: list<array<string, string>>} */
    public function inspect(string $path, int $previewLimit = 5): array
    {
        $rows = [];
        $headers = [];

        foreach ($this->rows($path) as $line => $row) {
            if ($line === 1) {
                $headers = array_values($row);

                continue;
            }

            if (count($rows) < $previewLimit) {
                $rows[] = $row;
            }
        }

        if ($headers === []) {
            throw new RuntimeException(__('imports::messages.errors.empty'));
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /** @return Generator<int, array<string, string>|list<string>> */
    public function rows(string $path): Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(__('imports::messages.errors.unreadable'));
        }

        try {
            $delimiter = $this->detectDelimiter($handle);
            $headers = null;
            $line = 0;

            while (($values = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $line++;
                $values = array_map($this->normalizeEncoding(...), $values);

                if ($this->isEmpty($values)) {
                    continue;
                }

                if ($headers === null) {
                    $headers = array_map(fn (string $header): string => trim(preg_replace('/^\xEF\xBB\xBF/', '', $header)), $values);

                    if (in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
                        throw new RuntimeException(__('imports::messages.errors.headers'));
                    }

                    yield 1 => $headers;

                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new RuntimeException(__('imports::messages.errors.columns', ['line' => $line]));
                }

                yield $line => array_combine($headers, $values);
            }

            if ($headers === null) {
                throw new RuntimeException(__('imports::messages.errors.empty'));
            }
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle */
    private function detectDelimiter($handle): string
    {
        $line = (string) fgets($handle);
        rewind($handle);
        $delimiters = [',', ';', "\t"];

        return collect($delimiters)
            ->sortByDesc(fn (string $delimiter): int => count(str_getcsv($line, $delimiter, '"', '')))
            ->first();
    }

    private function normalizeEncoding(mixed $value): string
    {
        $value = (string) $value;

        return mb_check_encoding($value, 'UTF-8')
            ? $value
            : mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    /** @param list<string> $values */
    private function isEmpty(array $values): bool
    {
        return collect($values)->every(fn (string $value): bool => trim($value) === '');
    }
}
