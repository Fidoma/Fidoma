<?php

namespace Tests\Feature\Filament;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Features\Invoices\Livewire\InvoiceDetails;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceDetailsSlideOverTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicking_an_invoice_opens_the_detail_slide_over_instead_of_the_edit_page(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createOpenInvoice($user);

        $component = Livewire::actingAs($user)
            ->test(ListInvoices::class)
            ->assertTableActionExists('viewDetails', fn (Action $action): bool => $action->isModalSlideOver())
            ->mountTableAction('viewDetails', $invoice)
            ->assertSee($invoice->invoice_number);

        $this->assertSame('viewDetails', $component->instance()->getTable()->getRecordAction($invoice));
        $this->assertNull($component->instance()->getTable()->getRecordUrl($invoice));
    }

    public function test_detail_view_shows_payment_summary_history_and_bank_mapping(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createOpenInvoice($user);
        $paymentMethod = PaymentMethod::query()->create(['payment_method_name' => 'Überweisung']);
        $payment = $invoice->payments()->create([
            'payment_method_id' => $paymentMethod->getKey(),
            'payment_date' => '2026-10-20',
            'payment_amount' => 40,
            'payment_note' => 'Erste Teilzahlung',
        ]);
        $transaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-20',
            'amount' => 40,
            'currency' => 'EUR',
            'counterparty_name' => 'Musterfirma GmbH',
            'purpose' => 'Teilzahlung',
            'pending' => false,
            'validated' => true,
        ]);
        $transaction->mappings()->create([
            'payment_id' => $payment->getKey(),
            'payment_created_by_mapping' => false,
        ]);
        $invoice->amount()->update([
            'invoice_paid' => 40,
            'invoice_balance' => 60,
        ]);

        Livewire::actingAs($user)
            ->test(InvoiceDetails::class, ['invoiceId' => $invoice->getKey()])
            ->assertSee('Zahlungsübersicht')
            ->assertSee('40 %')
            ->assertSee('Erste Teilzahlung')
            ->assertSee('Musterfirma GmbH')
            ->assertSee('Banktransaktion #'.$transaction->getKey())
            ->assertActionVisible('addPayment')
            ->assertActionVisible('createCredit')
            ->assertActionVisible('cancelInvoice')
            ->assertActionVisible('archive');
    }

    public function test_manual_payment_from_detail_view_reuses_the_payment_action_and_is_audited(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createOpenInvoice($user);
        $paymentMethod = PaymentMethod::query()->create(['payment_method_name' => 'Überweisung']);

        Livewire::actingAs($user)
            ->test(InvoiceDetails::class, ['invoiceId' => $invoice->getKey()])
            ->callAction('recordManualPayment', data: [
                'payment_date' => '2026-10-21',
                'payment_method_id' => $paymentMethod->getKey(),
                'payment_amount' => 25,
                'payment_note' => 'Im Slide-over erfasst',
            ])
            ->assertSee('Im Slide-over erfasst');

        $this->assertDatabaseHas('ip_payments', [
            'invoice_id' => $invoice->getKey(),
            'payment_amount' => 25,
            'payment_note' => 'Im Slide-over erfasst',
        ]);
        $this->assertDatabaseHas('ip_audit_logs', [
            'entity_type' => 'invoice',
            'entity_id' => $invoice->getKey(),
            'event_type' => 'invoice_payment_recorded',
            'status' => 'success',
        ]);
        $this->assertSame('25.00', $invoice->refresh()->amount->invoice_paid);
        $this->assertSame('75.00', $invoice->amount->invoice_balance);
    }

    public function test_draft_details_offer_issue_action_before_editing(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createOpenInvoice($user);
        $invoice->update([
            'invoice_number' => null,
            'invoice_status_id' => InvoiceStatus::Draft,
            'is_read_only' => false,
        ]);

        Livewire::actingAs($user)
            ->test(InvoiceDetails::class, ['invoiceId' => $invoice->getKey()])
            ->assertActionVisible('issueInvoice')
            ->assertSeeInOrder([
                'Rechnung ausstellen',
                'Rechnung bearbeiten',
            ]);
    }

    private function createOpenInvoice(User $user): Invoice
    {
        $client = Client::query()->create([
            'client_name' => 'Musterfirma GmbH',
            'client_active' => true,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice,
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'invoice_group_id' => 1,
            'invoice_status_id' => InvoiceStatus::Open,
            'is_read_only' => true,
            'is_archived' => false,
            'invoice_password' => '',
            'invoice_date_created' => '2026-10-10',
            'invoice_time_created' => '10:00:00',
            'invoice_date_due' => '2026-11-10',
            'invoice_number' => 'RE-2026-0142',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => Str::random(32),
            'payment_method' => 0,
            'invoice_einvoicing_version' => '',
        ]);
        $invoice->amount()->create([
            'invoice_sign' => 1,
            'invoice_item_subtotal' => 100,
            'invoice_item_tax_total' => 0,
            'invoice_tax_total' => 0,
            'invoice_total' => 100,
            'invoice_paid' => 0,
            'invoice_balance' => 100,
        ]);
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->getKey(),
            'item_tax_rate_id' => 0,
            'item_tax_included' => false,
            'item_name' => 'Leistung',
            'item_quantity' => 1,
            'item_price' => 100,
            'item_discount_amount' => 0,
            'item_order' => 1,
        ]);

        return $invoice;
    }
}
