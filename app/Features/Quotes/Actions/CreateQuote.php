<?php

namespace App\Features\Quotes\Actions;

use App\Features\InvoiceGroups\Actions\GenerateInvoiceGroupNumber;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Models\Quote;
use App\Features\Quotes\Services\QuoteDefaults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LogicException;

final class CreateQuote
{
    public function __construct(
        private readonly GenerateInvoiceGroupNumber $generateInvoiceGroupNumber,
        private readonly QuoteDefaults $defaults,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Quote
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('An authenticated user is required to create a quote.');
        }

        $invoiceGroupId = $this->defaults->invoiceGroupId();

        if ($invoiceGroupId === null) {
            throw new LogicException('No number group is configured for quotes.');
        }

        $quoteNumber = trim((string) ($attributes['quote_number'] ?? ''));

        return Quote::query()->create([
            ...$attributes,
            'invoice_id' => 0,
            'user_id' => $userId,
            'invoice_group_id' => $invoiceGroupId,
            'quote_status_id' => $attributes['quote_status_id'] ?? QuoteStatus::Draft->value,
            'quote_date_created' => $attributes['quote_date_created'] ?? now()->toDateString(),
            'quote_number' => $quoteNumber !== ''
                ? $quoteNumber
                : $this->generateInvoiceGroupNumber->handle($invoiceGroupId),
            'quote_url_key' => Str::random(32),
        ]);
    }
}
