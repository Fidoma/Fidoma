<?php

namespace Tests\Feature\Features\Documents;

use App\Features\BankTransactions\Models\BankTransaction;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Documents\Data\PaperlessDocument;
use App\Features\Documents\Queries\BankTransactionCandidateQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankTransactionCandidateQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_candidates_are_scored_first_and_positive_transactions_are_excluded(): void
    {
        $unrelated = $this->createTransaction('-30.0000', 'Anderer Lieferant', 'Sonstige Ausgabe');
        $matching = $this->createTransaction('-125.5000', 'Muster Lieferant GmbH', 'Rechnung RE-4711');
        $this->createTransaction('125.5000', 'Muster Lieferant GmbH', 'Rechnung RE-4711');
        $document = new PaperlessDocument(10, 'Rechnung', 'Muster Lieferant', 'RE-4711', '2026-10-14', 125.50);

        $candidates = app(BankTransactionCandidateQuery::class)->query($document)->get();

        $this->assertSame($matching->getKey(), $candidates->first()->getKey());
        $this->assertSame(100, (int) $candidates->first()->match_score);
        $this->assertSame([$matching->getKey(), $unrelated->getKey()], $candidates->pluck('id')->all());
    }

    public function test_search_filters_assignment_status_account_date_and_amount_with_pagination(): void
    {
        $assigned = $this->createTransaction('-49.9900', 'Alpha Handel', 'Rechnung FILTER-1', 'DE001', '2026-10-01');
        BankTransactionMapping::query()->create([
            'bank_transaction_id' => $assigned->getKey(),
            'payment_id' => null,
            'document_id' => 900,
        ]);
        $this->createTransaction('-49.9900', 'Beta Handel', 'Andere Rechnung', 'DE002', '2026-10-01');

        for ($index = 0; $index < 12; $index++) {
            $this->createTransaction('-10.0000', "Lieferant {$index}", 'Pagination');
        }

        $document = new PaperlessDocument(11, 'Rechnung', null, null, null, null);
        $query = app(BankTransactionCandidateQuery::class);
        $filtered = $query->query($document, [
            'search' => 'FILTER-1',
            'amount' => '49,99',
            'booking_date' => '2026-10-01',
            'account_iban' => 'DE001',
            'status' => 'assigned',
        ])->get();
        $paginated = $query->query($document)->paginate(10);

        $this->assertSame([$assigned->getKey()], $filtered->pluck('id')->all());
        $this->assertSame(14, $paginated->total());
        $this->assertCount(10, $paginated->items());
    }

    private function createTransaction(
        string $amount,
        string $counterparty = 'Lieferant',
        string $purpose = 'Ausgabe',
        string $accountIban = 'DE02120300000000202051',
        string $bookingDate = '2026-10-14',
    ): BankTransaction {
        return BankTransaction::query()->create([
            'account_iban' => $accountIban,
            'booking_date' => $bookingDate,
            'amount' => $amount,
            'currency' => 'EUR',
            'counterparty_name' => $counterparty,
            'purpose' => $purpose,
            'transaction_reference' => hash('sha256', fake()->unique()->uuid()),
            'pending' => false,
            'validated' => false,
        ]);
    }
}
