<?php

namespace App\Features\Documents\Data;

use DateTimeImmutable;

final readonly class PaperlessDocument
{
    /**
     * @param  array<int, string>  $correspondents
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $supplier,
        public ?string $invoiceNumber,
        public ?string $documentDate,
        public ?float $amount,
        public string $currency = 'EUR',
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $correspondents
     */
    public static function fromPaperless(array $payload, array $correspondents = []): self
    {
        $customFields = collect($payload['custom_fields'] ?? [])
            ->filter(fn (mixed $field): bool => is_array($field))
            ->mapWithKeys(fn (array $field): array => [(int) ($field['field'] ?? 0) => $field['value'] ?? null]);

        $correspondent = $payload['correspondent'] ?? null;
        $supplier = is_array($correspondent)
            ? (string) ($correspondent['name'] ?? '')
            : ($correspondents[(int) $correspondent] ?? null);

        $amountValue = $customFields->get((int) config('services.paperless.amount_field_id'));
        $amount = is_scalar($amountValue)
            ? filter_var(str_replace(',', '.', (string) $amountValue), FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE)
            : null;

        $documentDate = (string) ($payload['document_date'] ?? $payload['created'] ?? '');
        if ($documentDate !== '' && DateTimeImmutable::createFromFormat('!Y-m-d', substr($documentDate, 0, 10)) === false) {
            $documentDate = '';
        }

        return new self(
            id: (int) ($payload['id'] ?? 0),
            title: trim((string) ($payload['title'] ?? '')),
            supplier: filled($supplier) ? trim((string) $supplier) : null,
            invoiceNumber: filled($invoiceNumber = $customFields->get((int) config('services.paperless.invoice_number_field_id')))
                ? trim((string) $invoiceNumber)
                : null,
            documentDate: $documentDate !== '' ? substr($documentDate, 0, 10) : null,
            amount: is_float($amount) ? $amount : null,
        );
    }

    /** @return array<string, int|float|string|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'supplier' => $this->supplier,
            'invoiceNumber' => $this->invoiceNumber,
            'documentDate' => $this->documentDate,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
