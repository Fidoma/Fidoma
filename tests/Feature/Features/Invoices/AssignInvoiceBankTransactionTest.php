<?php

namespace Tests\Feature\Features\Invoices;

use App\Features\BankTransactions\Actions\CreateBankTransaction;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\AssignInvoiceBankTransactions;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Actions\UnassignInvoiceBankTransactions;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\Payments\Actions\AssignPaymentBankTransactions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssignInvoiceBankTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_an_incoming_transaction_as_an_invoice_payment_and_can_unmap_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $client = Client::query()->create([
            'client_name' => 'Musterkunde GmbH',
            'client_active' => true,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice,
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'invoice_group_id' => 1,
            'invoice_status_id' => InvoiceStatus::Open,
            'is_read_only' => false,
            'is_archived' => false,
            'invoice_password' => '',
            'invoice_date_created' => '2026-10-14',
            'invoice_time_created' => '10:00:00',
            'invoice_date_due' => '2026-10-28',
            'invoice_number' => 'RE-2026-2001',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => 'income-mapping-action-test',
            'payment_method' => 0,
            'creditinvoice_parent_id' => null,
            'invoice_einvoicing_version' => '',
        ]);
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->getKey(),
            'item_tax_rate_id' => 0,
            'item_tax_included' => false,
            'item_name' => 'Leistung',
            'item_quantity' => 1,
            'item_price' => 119,
            'item_discount_amount' => 0,
            'item_order' => 1,
        ]);
        app(RecalculateInvoice::class)->handle($invoice);

        $transaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-15',
            'amount' => '1119.0000',
            'currency' => 'EUR',
            'counterparty_name' => 'Musterkunde GmbH',
            'purpose' => 'Zahlung RE-2026-2001',
            'pending' => false,
        ]);

        $mapping = app(AssignInvoiceBankTransactions::class)->handleMany($invoice->getKey(), [
            $transaction->getKey() => $transaction->assignmentFingerprint(),
        ])->sole();

        $this->assertNotNull($mapping->payment_id);
        $this->assertDatabaseHas('ip_payments', [
            'payment_id' => $mapping->payment_id,
            'invoice_id' => $invoice->getKey(),
            'payment_amount' => 1119,
        ]);
        $this->assertDatabaseHas('ip_bank_transaction_mappings', [
            'bank_transaction_mapping_id' => $mapping->getKey(),
            'payment_created_by_mapping' => true,
        ]);
        $this->assertSame('-1000.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Paid, $invoice->invoice_status_id);
        $this->assertTrue($transaction->refresh()->validated);

        app(UnassignInvoiceBankTransactions::class)->handleMany($invoice->getKey(), [
            $transaction->getKey() => $transaction->assignmentFingerprint(),
        ]);

        $this->assertDatabaseMissing('ip_bank_transaction_mappings', [
            'bank_transaction_mapping_id' => $mapping->getKey(),
        ]);
        $this->assertDatabaseMissing('ip_payments', [
            'payment_id' => $mapping->payment_id,
        ]);
        $this->assertSame('119.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Open, $invoice->invoice_status_id);
        $this->assertFalse($transaction->refresh()->validated);

        $payment = $invoice->payments()->create([
            'payment_method_id' => app(InvoiceDefaults::class)->paymentMethodId(),
            'payment_date' => '2026-10-16',
            'payment_amount' => 119,
            'payment_note' => 'Manuell erfasst',
        ]);
        app(RecalculateInvoice::class)->handle($invoice);
        $this->assertSame('0.00', $invoice->refresh()->amount->invoice_balance);

        $paymentTransaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-16',
            'amount' => '119.0000',
            'currency' => 'EUR',
            'counterparty_name' => 'Musterkunde GmbH',
            'purpose' => 'Zahlung RE-2026-2001',
            'pending' => false,
        ]);

        $paymentMapping = app(AssignPaymentBankTransactions::class)->handleMany($payment->getKey(), [
            $paymentTransaction->getKey() => $paymentTransaction->assignmentFingerprint(),
        ])->sole();

        $this->assertSame($payment->getKey(), $paymentMapping->payment_id);
        $this->assertFalse($paymentMapping->payment_created_by_mapping);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertTrue($paymentTransaction->refresh()->validated);

        app(UnassignInvoiceBankTransactions::class)->handleMany($invoice->getKey(), [
            $paymentTransaction->getKey() => $paymentTransaction->assignmentFingerprint(),
        ]);

        $this->assertTrue($payment->fresh()->exists);
        $this->assertFalse($paymentTransaction->refresh()->validated);

        $invoiceTransaction = app(CreateBankTransaction::class)->handle([
            'account_iban' => 'DE02120300000000202051',
            'booking_date' => '2026-10-17',
            'amount' => '119.0000',
            'currency' => 'EUR',
            'counterparty_name' => 'Musterkunde GmbH',
            'purpose' => 'Weitere Zahlung RE-2026-2001',
            'pending' => false,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Die Rechnung hat keinen offenen Betrag.');

        app(AssignInvoiceBankTransactions::class)->handleMany($invoice->getKey(), [
            $invoiceTransaction->getKey() => $invoiceTransaction->assignmentFingerprint(),
        ]);
    }
}
