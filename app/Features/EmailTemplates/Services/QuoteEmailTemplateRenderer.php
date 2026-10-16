<?php

namespace App\Features\EmailTemplates\Services;

use App\Features\Quotes\Models\Quote;

final class QuoteEmailTemplateRenderer
{
    public function render(Quote $quote, ?string $content, bool $escapeValues = false): string
    {
        $quote->loadMissing(['amount', 'client', 'user']);

        $client = $quote->client;
        $amount = $quote->amount;
        $user = $quote->user;

        $values = [
            'client_name' => $client?->client_name,
            'client_company' => $client?->client_company,
            'client_email' => $client?->client_email,
            'quote_number' => $quote->quote_number,
            'quote_date_created' => $quote->quote_date_created?->format('d.m.Y'),
            'quote_date_expires' => $quote->quote_date_expires?->format('d.m.Y'),
            'quote_total' => $this->money($amount?->quote_total),
            'quote_notes' => $quote->notes,
            'user_name' => $user?->name,
            'user_company' => $user?->company_name,
            'user_email' => $user?->email,
            'user_phone' => $user?->phone,
            'user_web' => $user?->website,
        ];

        $replacements = [];
        foreach ($values as $key => $value) {
            $replacement = (string) ($value ?? '');
            $replacements['{{{'.$key.'}}}'] = $escapeValues ? e($replacement) : $replacement;
        }

        return strtr((string) $content, $replacements);
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.').' €';
    }
}
