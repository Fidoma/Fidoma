<?php

namespace App\Features\Quotes\Actions;

use App\Features\Quotes\Models\Quote;

final class ArchiveQuote
{
    public function handle(Quote $quote): Quote
    {
        $quote->update(['is_archived' => true]);

        return $quote;
    }
}
