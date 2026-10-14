<?php

namespace Tests\Feature;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_bank_transaction_with_an_internal_reference(): void
    {
        $transaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-13',
            'value_date' => '2026-10-14',
            'amount' => '-125.5000',
            'currency' => 'eur',
            'counterparty_name' => 'Muster Lieferant GmbH',
            'purpose' => 'Eingangsrechnung 2026-100',
            'pending' => false,
        ]);

        $this->assertDatabaseHas('ip_bank_transactions', [
            'id' => $transaction->getKey(),
            'currency' => 'EUR',
            'validated' => false,
        ]);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $transaction->transaction_reference);
    }

    public function test_authenticated_user_can_open_bank_transaction_index(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/bank-transactions')
            ->assertOk()
            ->assertSee('Banktransaktion');
    }
}
