<?php

namespace App\Features\Imports\Services;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Features\BankTransactions\Models\BankTransaction;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BankTransactionCsvImporter
{
    /** @var list<string> */
    public const FIELDS = [
        'account_iban', 'booking_date', 'value_date', 'amount', 'currency', 'purpose',
        'counterparty_name', 'counterparty_iban', 'booking_text', 'transaction_reference',
        'bank_transaction_id', 'bank_reference', 'end_to_end_id', 'pending', 'validated',
    ];

    public function __construct(
        private readonly CsvReader $reader,
        private readonly CreateBankTransaction $createTransaction,
    ) {}

    /** @param list<string> $headers
     * @return array<string, string>
     */
    public function suggestMapping(array $headers): array
    {
        $aliases = [
            'account_iban' => ['account_iban', 'iban', 'eigene iban', 'konto', 'auftragskonto'],
            'booking_date' => ['booking_date', 'date', 'transaction_date', 'buchungstag', 'buchungsdatum'],
            'value_date' => ['value_date', 'wertstellung', 'valutadatum'],
            'amount' => ['amount', 'transaction_amount', 'betrag', 'umsatz'],
            'currency' => ['currency', 'currency_code', 'währung', 'waehrung'],
            'purpose' => ['purpose', 'description', 'memo', 'verwendungszweck'],
            'counterparty_name' => ['counterparty_name', 'participant_name', 'payee', 'auftraggeber', 'begünstigter/zahlungspflichtiger', 'beguenstigter/zahlungspflichtiger', 'name zahlungsbeteiligter'],
            'counterparty_iban' => ['counterparty_iban', 'participant', 'iban zahlungsbeteiligter', 'iban auftraggeber', 'kontonummer/iban'],
            'booking_text' => ['booking_text', 'category_type', 'buchungstext', 'umsatzart'],
            'transaction_reference' => ['transaction_reference', 'nordigen_transaction_id', 'external_id', 'id', 'transaktionsreferenz', 'kundenreferenz (end-to-end)'],
            'bank_transaction_id' => ['bank_transaction_id', 'transaction_id', 'banktransaktions-id'],
            'bank_reference' => ['bank_reference', 'kundenreferenz', 'bankreferenz', 'sammlerreferenz'],
            'end_to_end_id' => ['end_to_end_id', 'end-to-end-referenz', 'end to end id', 'kundenreferenz (end-to-end)'],
            'pending' => ['pending', 'vorgemerkt', 'info'],
            'validated' => ['validated', 'validiert'],
        ];
        $normalizedHeaders = collect($headers)->mapWithKeys(fn (string $header): array => [$this->key($header) => $header]);

        return collect($aliases)->mapWithKeys(function (array $candidates, string $field) use ($normalizedHeaders): array {
            foreach ($candidates as $candidate) {
                if ($header = $normalizedHeaders->get($this->key($candidate))) {
                    return [$field => $header];
                }
            }

            return [$field => ''];
        })->all();
    }

    /**
     * @param  array<string, string>  $mapping
     * @param  array<string, mixed>  $defaults
     * @param  array{column?: string|null, negative_value?: string|null, positive_value?: string|null}  $signRule
     */
    public function import(string $path, array $mapping, array $defaults, array $signRule = []): int
    {
        $headers = $this->reader->inspect($path, 0)['headers'];
        $this->validateMapping($headers, $mapping, $signRule);

        return DB::transaction(function () use ($path, $mapping, $defaults, $signRule): int {
            $count = 0;

            foreach ($this->reader->rows($path) as $line => $sourceRow) {
                if ($line === 1) {
                    continue;
                }

                $attributes = [];

                foreach (self::FIELDS as $field) {
                    $source = (string) ($mapping[$field] ?? '');
                    $value = $source !== '' ? ($sourceRow[$source] ?? '') : '';
                    $attributes[$field] = $this->normalize($field, $value !== '' ? $value : ($defaults[$field] ?? ''));
                }

                $attributes['transaction_reference'] = $this->reference($attributes, $sourceRow);
                $attributes['amount'] = $this->applySignRule((string) $attributes['amount'], $sourceRow, $signRule, $line);
                $this->validateRow($attributes, $line);

                if (BankTransaction::query()->where('transaction_reference', $attributes['transaction_reference'])->exists()) {
                    throw ValidationException::withMessages([
                        'data.csv_file' => __('imports::messages.errors.duplicate', ['line' => $line]),
                    ]);
                }

                $this->createTransaction->handle($attributes);
                $count++;
            }

            if ($count === 0) {
                throw ValidationException::withMessages(['data.csv_file' => __('imports::messages.errors.no_rows')]);
            }

            return $count;
        });
    }

    /** @param list<string> $headers
     * @param  array<string, string>  $mapping
     * @param  array<string, mixed>  $signRule
     */
    private function validateMapping(array $headers, array $mapping, array $signRule): void
    {
        foreach ($mapping as $field => $source) {
            $source = (string) $source;

            if (! in_array($field, self::FIELDS, true) || ($source !== '' && ! in_array($source, $headers, true))) {
                throw ValidationException::withMessages(['data.csv_file' => __('imports::messages.errors.mapping')]);
            }
        }

        $signColumn = (string) ($signRule['column'] ?? '');

        if ($signColumn !== '' && ! in_array($signColumn, $headers, true)) {
            throw ValidationException::withMessages(['data.sign_rule.column' => __('imports::messages.errors.mapping')]);
        }
    }

    /** @param array<string, mixed> $attributes
     * @param  array<string, string>  $sourceRow
     */
    private function reference(array $attributes, array $sourceRow): string
    {
        $reference = trim((string) $attributes['transaction_reference']);

        if ($reference !== '' && preg_match('/^[0-9A-Za-z]{64}$/', $reference)) {
            return $reference;
        }

        $source = $reference !== '' ? $reference : json_encode($sourceRow, JSON_UNESCAPED_UNICODE);

        return hash('sha256', $attributes['account_iban'].'|'.$source);
    }

    private function normalize(string $field, mixed $value): string|bool|null
    {
        $value = trim(strip_tags((string) $value));

        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return in_array($field, ['pending', 'validated'], true) ? false : null;
        }

        if (in_array($field, ['booking_date', 'value_date'], true)) {
            foreach (['Y-m-d', 'd.m.Y', 'd.m.y', 'm/d/Y'] as $format) {
                $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

                if ($date !== false && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            }

            return $value;
        }

        if ($field === 'amount') {
            return $this->normalizeAmount($value);
        }

        if (in_array($field, ['account_iban', 'counterparty_iban', 'currency'], true)) {
            return mb_strtoupper(str_replace(' ', '', $value));
        }

        if (in_array($field, ['pending', 'validated'], true)) {
            return in_array(mb_strtolower($value), ['1', 'true', 'ja', 'yes', 'umsatz vorgemerkt', 'vorgemerkt'], true);
        }

        return $value;
    }

    private function normalizeAmount(string $value): string
    {
        $value = str_replace(["\u{00A0}", ' ', '€'], '', $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace(',', '.', str_replace('.', '', $value))
                : str_replace(',', '', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    /** @param array<string, mixed> $attributes */
    private function validateRow(array $attributes, int $line): void
    {
        foreach (['account_iban', 'booking_date', 'amount', 'currency'] as $field) {
            if (blank($attributes[$field])) {
                $this->invalid($line, $field);
            }
        }

        if (! is_numeric($attributes['amount']) || ! preg_match('/^[A-Z]{3}$/', (string) $attributes['currency'])) {
            $this->invalid($line, ! is_numeric($attributes['amount']) ? 'amount' : 'currency');
        }

        foreach (['booking_date', 'value_date'] as $field) {
            if (filled($attributes[$field])) {
                $value = (string) $attributes[$field];
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

                if ($date === false || $date->format('Y-m-d') !== $value) {
                    $this->invalid($line, $field);
                }
            }
        }

        if (mb_strlen((string) $attributes['account_iban']) > 34) {
            $this->invalid($line, 'account_iban');
        }
    }

    /** @param array<string, string> $sourceRow
     * @param  array<string, mixed>  $signRule
     */
    private function applySignRule(string $amount, array $sourceRow, array $signRule, int $line): string
    {
        $column = (string) ($signRule['column'] ?? '');

        if ($column === '') {
            return $amount;
        }

        $source = trim((string) ($sourceRow[$column] ?? ''));
        $negative = trim((string) ($signRule['negative_value'] ?? 'DEBIT'));
        $positive = trim((string) ($signRule['positive_value'] ?? 'CREDIT'));

        if ($negative === '' || $positive === '' || strcasecmp($negative, $positive) === 0) {
            throw ValidationException::withMessages(['data.sign_rule.column' => __('imports::messages.errors.sign_configuration')]);
        }

        if (strcasecmp($source, $negative) === 0) {
            return '-'.ltrim($amount, '+-');
        }

        if (strcasecmp($source, $positive) === 0) {
            return ltrim($amount, '+-');
        }

        throw ValidationException::withMessages([
            'data.sign_rule.column' => __('imports::messages.errors.sign_value', ['line' => $line, 'value' => $source]),
        ]);
    }

    private function invalid(int $line, string $field): never
    {
        throw ValidationException::withMessages([
            'data.csv_file' => __('imports::messages.errors.invalid', ['line' => $line, 'field' => $field]),
        ]);
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/[^\pL\pN]+/u', ' ', $value)));
    }
}
