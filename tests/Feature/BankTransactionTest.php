<?php

namespace Tests\Feature;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Features\BankTransactions\Filament\Resources\BankTransactions\Pages\ListBankTransactions;
use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Documents\Services\PaperlessClient;
use App\Models\User;
use Filament\Actions\ViewAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_all_tab_is_first_while_open_tab_is_selected_by_default(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(ListBankTransactions::class)
            ->assertSet('activeTab', 'open');

        $this->assertSame(
            ['all', 'open', 'assigned'],
            array_keys($component->instance()->getTabs()),
        );
    }

    public function test_clicking_a_transaction_opens_the_read_only_detail_slide_over(): void
    {
        $transaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-13',
            'value_date' => '2026-10-14',
            'amount' => '-125.50',
            'currency' => 'EUR',
            'counterparty_name' => 'Muster Lieferant GmbH',
            'counterparty_iban' => 'DE12500105170648489890',
            'purpose' => 'Eingangsrechnung 2026-100',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListBankTransactions::class)
            ->assertTableActionExists('view', fn (ViewAction $action): bool => $action->isModalSlideOver())
            ->mountTableAction('view', $transaction)
            ->assertSchemaComponentExists('remaining_amount')
            ->assertSchemaComponentStateSet('counterparty_name', 'Muster Lieferant GmbH')
            ->assertSchemaComponentStateSet('purpose', 'Eingangsrechnung 2026-100');
    }

    public function test_detail_action_is_only_exposed_through_the_table_row(): void
    {
        app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-13',
            'amount' => '25.00',
            'currency' => 'EUR',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListBankTransactions::class)
            ->assertSeeHtml('mountTableAction(&#039;view&#039;')
            ->assertSeeHtml('style="display: none;"');
    }

    public function test_detail_slide_over_shows_linked_document_without_changing_the_transaction(): void
    {
        $transaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-13',
            'amount' => '-75.00',
            'currency' => 'EUR',
        ]);
        BankTransactionMapping::query()->create([
            'bank_transaction_id' => $transaction->getKey(),
            'document_id' => 4711,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListBankTransactions::class)
            ->set('activeTab', 'all')
            ->mountTableAction('view', $transaction)
            ->assertSchemaComponentStateSet('linked_records', [[
                'type' => 'Dokument',
                'label' => 'Paperless-Dokument #4711',
                'detail' => null,
                'amount' => null,
                'currency' => 'EUR',
                'url' => app(PaperlessClient::class)->documentDetailsUrl(4711),
                'external' => true,
            ]])
            ->assertSchemaComponentStateSet('remaining_amount', 0.0);

        $this->assertSame('-75.0000', $transaction->fresh()->amount);
        $this->assertFalse($transaction->fresh()->validated);
    }
}
