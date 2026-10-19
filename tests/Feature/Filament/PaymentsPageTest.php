<?php

namespace Tests\Feature\Filament;

use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\IssueInvoice;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\Invoices\Services\InvoiceDefaults;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Actions\DeletePayment;
use App\Features\Payments\Filament\Resources\Payments\Pages\ListPayments;
use App\Features\Payments\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/payments')->assertRedirect('/admin/login');
    }

    public function test_all_payments_are_listed_with_customer_and_invoice_information(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Musterkunde GmbH',
            'client_active' => true,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice,
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'invoice_group_id' => 1,
            'invoice_status_id' => InvoiceStatus::Paid,
            'is_read_only' => true,
            'is_archived' => false,
            'invoice_password' => '',
            'invoice_date_created' => '2026-10-14',
            'invoice_time_created' => '10:00:00',
            'invoice_date_due' => '2026-10-28',
            'invoice_number' => 'RE-2026-3001',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => 'payments-page-test',
            'payment_method' => 0,
            'creditinvoice_parent_id' => null,
            'invoice_einvoicing_version' => '',
        ]);
        $cash = PaymentMethod::query()->create(['payment_method_name' => 'Bar']);
        $transfer = PaymentMethod::query()->create(['payment_method_name' => 'Überweisung']);
        $card = PaymentMethod::query()->create(['payment_method_name' => 'Card']);
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'payment_method_id' => $cash->getKey(),
            'payment_date' => '2026-10-15',
            'payment_amount' => 119,
            'payment_note' => 'Zahlung per Banktransaktion',
        ]);
        $transferPayment = Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'payment_method_id' => $transfer->getKey(),
            'payment_date' => '2026-10-14',
            'payment_amount' => 20,
            'payment_note' => 'Überwiesen',
        ]);
        $cardPayment = Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'payment_method_id' => $card->getKey(),
            'payment_date' => '2026-10-13',
            'payment_amount' => 10,
            'payment_note' => 'Kartenzahlung',
        ]);
        $legacyPayment = Payment::query()->create([
            'invoice_id' => $invoice->getKey(),
            'payment_method_id' => 0,
            'payment_date' => '2026-10-12',
            'payment_amount' => 5,
            'payment_note' => 'Ohne gespeicherte Zahlungsart',
        ]);

        $component = Livewire::actingAs($user)
            ->test(ListPayments::class)
            ->assertCanSeeTableRecords([$payment, $transferPayment, $cardPayment])
            ->assertSee('Alle')
            ->assertSee('Bar')
            ->assertSee('Überweisung')
            ->assertSee('Card')
            ->assertSee('Musterkunde GmbH')
            ->assertSee('RE-2026-3001')
            ->assertSee('Zahlung per Banktransaktion')
            ->assertSee('119,00')
            ->set('activeTab', 'method-'.$cash->getKey())
            ->assertCanSeeTableRecords([$payment])
            ->assertCanNotSeeTableRecords([$transferPayment, $cardPayment]);

        $component
            ->set('activeTab', 'all')
            ->mountTableAction('editPayment', $legacyPayment)
            ->assertTableActionDataSet([
                'payment_method_id' => app(InvoiceDefaults::class)->paymentMethodId(),
            ])
            ->unmountTableAction();

        $this->assertNull($component->instance()->getTable()->getRecordUrl($payment));
        $this->assertSame('editPayment', $component->instance()->getTable()->getRecordAction($payment));

        BankTransactionMapping::query()->create([
            'bank_transaction_id' => 999,
            'payment_id' => $payment->getKey(),
            'document_id' => null,
        ]);

        $component->mountTableAction('editPayment', $payment);

        $deleteAction = $component->instance()->getMountedAction()?->getModalFooterActions()['deletePayment'];
        $this->assertNotNull($deleteAction);
        $this->assertTrue($deleteAction->isDisabled());

        try {
            app(DeletePayment::class)->handle($payment);
            $this->fail('Eine verknüpfte Zahlung durfte gelöscht werden.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Eine mit einer Banktransaktion verknüpfte Zahlung kann nicht gelöscht werden.',
                $exception->errors()['payment'][0],
            );
        }

        $this->assertDatabaseHas('ip_payments', ['payment_id' => $payment->getKey()]);
    }

    public function test_a_payment_can_be_added_for_an_open_invoice(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Neukunde GmbH',
            'client_active' => true,
        ]);
        $method = PaymentMethod::query()->create([
            'payment_method_name' => 'Überweisung',
        ]);
        $invoice = Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice,
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'invoice_group_id' => 3,
            'invoice_status_id' => InvoiceStatus::Draft,
            'is_read_only' => false,
            'is_archived' => false,
            'invoice_password' => '',
            'invoice_date_created' => '2026-10-14',
            'invoice_time_created' => '10:00:00',
            'invoice_date_due' => '2026-10-28',
            'invoice_number' => null,
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => 'payments-create-test',
            'payment_method' => $method->getKey(),
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
        app(IssueInvoice::class)->handle($invoice);

        Livewire::actingAs($user)
            ->test(ListPayments::class)
            ->assertTableActionExists('createPayment')
            ->callTableAction('createPayment', data: [
                'invoice_id' => $invoice->getKey(),
                'payment_method_id' => $method->getKey(),
                'payment_amount' => 50,
                'payment_date' => '2026-10-15',
                'payment_note' => 'Erste Teilzahlung',
            ])
            ->assertNotified('Zahlung wurde gespeichert.');

        $this->assertDatabaseHas('ip_payments', [
            'invoice_id' => $invoice->getKey(),
            'payment_method_id' => $method->getKey(),
            'payment_amount' => 50,
            'payment_date' => '2026-10-15',
            'payment_note' => 'Erste Teilzahlung',
        ]);
        $this->assertSame('69.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Open, $invoice->invoice_status_id);
        $this->assertNotNull($invoice->invoice_number);

        $firstPayment = $invoice->payments()->firstOrFail();

        Livewire::actingAs($user)
            ->test(ListPayments::class)
            ->callTableAction('editPayment', $firstPayment, data: [
                'payment_method_id' => $method->getKey(),
                'payment_amount' => 40,
                'payment_date' => '2026-10-17',
                'payment_note' => 'Korrigierte Teilzahlung',
            ])
            ->assertNotified('Zahlung wurde gespeichert.');

        $this->assertDatabaseHas('ip_payments', [
            'payment_id' => $firstPayment->getKey(),
            'payment_amount' => 40,
            'payment_date' => '2026-10-17',
            'payment_note' => 'Korrigierte Teilzahlung',
        ]);
        $this->assertSame('79.00', $invoice->refresh()->amount->invoice_balance);

        Livewire::actingAs($user)
            ->test(ListPayments::class)
            ->callTableAction('createPayment', data: [
                'invoice_id' => $invoice->getKey(),
                'payment_method_id' => $method->getKey(),
                'payment_amount' => 79,
                'payment_date' => '2026-10-16',
                'payment_note' => 'Restzahlung',
            ])
            ->assertNotified('Zahlung wurde gespeichert.');

        $this->assertSame('0.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Paid, $invoice->invoice_status_id);

        app(DeletePayment::class)->handle($firstPayment);

        $this->assertDatabaseMissing('ip_payments', ['payment_id' => $firstPayment->getKey()]);
        $this->assertSame('40.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Open, $invoice->invoice_status_id);
    }
}
