<?php

use App\Features\Quotes\Actions\RecalculateQuote;
use App\Features\Quotes\Models\Quote;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $recalculateQuote = app(RecalculateQuote::class);

        Quote::query()
            ->orderBy('quote_id')
            ->each(fn (Quote $quote) => $recalculateQuote->handle($quote));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Calculated amount records remain valid when rolling back application code.
    }
};
