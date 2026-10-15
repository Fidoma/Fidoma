<?php

namespace Tests\Feature\Filament;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\RecalculateInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Filament\Resources\Payments\Pages\ListPayments;
use App\Features\Payments\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertSame(
            InvoiceResource::getUrl('edit', ['record' => $invoice]),
            $component->instance()->getTable()->getRecordUrl($payment),
        );
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
            'invoice_group_id' => 1,
            'invoice_status_id' => InvoiceStatus::Sent,
            'is_read_only' => false,
            'is_archived' => false,
            'invoice_password' => '',
            'invoice_date_created' => '2026-10-14',
            'invoice_time_created' => '10:00:00',
            'invoice_date_due' => '2026-10-28',
            'invoice_number' => 'RE-2026-3002',
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
        $this->assertSame(InvoiceStatus::Sent, $invoice->invoice_status_id);

        Livewire::actingAs($user)
            ->test(ListPayments::class)
            ->callTableAction('createPayment', data: [
                'invoice_id' => $invoice->getKey(),
                'payment_method_id' => $method->getKey(),
                'payment_amount' => 69,
                'payment_date' => '2026-10-16',
                'payment_note' => 'Restzahlung',
            ])
            ->assertNotified('Zahlung wurde gespeichert.');

        $this->assertSame('0.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Paid, $invoice->invoice_status_id);
    }
}
