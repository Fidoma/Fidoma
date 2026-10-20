<?php

namespace Tests\Feature\Filament;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Features\Invoices\Models\InvoiceAmount;
use App\Features\Invoices\Models\InvoiceItem;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Terminals\Contracts\PaymentTerminalInterface;
use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Enums\TerminalConnectionStatus;
use App\Features\Terminals\Enums\TerminalOperatingStatus;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Filament\Pages\Terminal;
use App\Features\Terminals\Filament\Resources\PaymentTerminals\PaymentTerminalResource;
use App\Features\Terminals\Models\PaymentTerminal;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use App\Features\Terminals\Services\TerminalPaymentService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TerminalPageTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<int> */
    private array $invoiceIds = [];

    /** @var list<int> */
    private array $clientIds = [];

    /** @var list<int> */
    private array $paymentMethodIds = [];

    public function test_invoice_list_can_be_searched_filtered_sorted_and_selected(): void
    {
        $user = User::factory()->create();
        $open = $this->invoice($user, 'RE-OPEN', 'Alpha GmbH', 100, 0, '2026-10-01');
        $partial = $this->invoice($user, 'RE-PART', 'Beta GmbH', 200, 50, '2026-10-02');

        Livewire::actingAs($user)->test(Terminal::class)
            ->assertSee('RE-OPEN')->assertSee('RE-PART')
            ->set('invoiceFilter', 'partial')
            ->assertDontSee('RE-OPEN')->assertSee('RE-PART')
            ->set('invoiceFilter', 'all')->set('search', 'Alpha')
            ->assertSee('RE-OPEN')->assertDontSee('RE-PART')
            ->set('search', '')->set('sort', 'amount_desc')
            ->call('selectInvoice', $partial->getKey())
            ->assertSet('selectedInvoiceId', $partial->getKey())
            ->assertSet('amount', '150.00')
            ->assertSee('150,00');

        $this->assertSame('100.00', $open->amount->invoice_balance);
    }

    public function test_simulator_attempt_is_visible_but_never_posts_a_payment(): void
    {
        config()->set('terminal.mode', 'simulator');
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        $invoice = $this->invoice($user, 'RE-DEMO', 'Demo GmbH', 100, 0);
        $terminal = $this->terminal($user, $method);

        Livewire::actingAs($user)->test(Terminal::class)
            ->call('selectInvoice', $invoice->getKey())
            ->set('selectedTerminalId', $terminal->getKey())
            ->set('amount', '40.00')
            ->call('startPayment')
            ->assertSee('Demo erfolgreich')
            ->assertNotified('Demo erfolgreich. Es wurde keine Zahlung verbucht.');

        $this->assertSame(0, $invoice->payments()->count());
        $this->assertDatabaseHas('terminal_payment_attempts', [
            'invoice_id' => $invoice->getKey(),
            'status' => TerminalPaymentStatus::DemoSuccessful->value,
            'gateway_mode' => 'simulator',
            'payment_id' => null,
        ]);
        $this->assertSame('100.00', $invoice->refresh()->amount->invoice_balance);
    }

    public function test_confirmed_real_partial_payment_uses_existing_invoice_posting_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        $invoice = $this->invoice($user, 'RE-CARD', 'Kartenkunde', 100, 0);
        $terminal = $this->terminal($user, $method);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::Successful));
        $service = $this->app->make(TerminalPaymentService::class);

        $attempt = $service->start($invoice, $terminal, 40, $user);
        $service->refresh($attempt, $user);

        $this->assertNotNull($attempt->refresh()->payment_id);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertDatabaseHas('ip_payments', ['invoice_id' => $invoice->getKey(), 'payment_amount' => 40]);
        $this->assertSame('60.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Open, $invoice->invoice_status_id);
    }

    public function test_terminal_and_invoice_are_locked_while_a_payment_is_active(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        $firstInvoice = $this->invoice($user, 'RE-1', 'Kunde 1', 100, 0);
        $secondInvoice = $this->invoice($user, 'RE-2', 'Kunde 2', 100, 0);
        $terminal = $this->terminal($user, $method);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::AwaitingCard));
        $service = $this->app->make(TerminalPaymentService::class);
        $service->start($firstInvoice, $terminal, 20, $user);

        $this->expectException(ValidationException::class);
        $service->start($secondInvoice, $terminal, 20, $user);
    }

    public function test_an_invoice_cannot_be_started_on_a_second_terminal_while_payment_is_active(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        $invoice = $this->invoice($user, 'RE-DUPLICATE', 'Kunde', 100, 0);
        $firstTerminal = $this->terminal($user, $method);
        $secondTerminal = $this->terminal($user, $method);
        $secondTerminal->update(['name' => 'Kasse 2', 'host' => '192.0.2.11']);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::AwaitingCard));
        $service = $this->app->make(TerminalPaymentService::class);
        $service->start($invoice, $firstTerminal, 20, $user);

        $this->expectException(ValidationException::class);
        $service->start($invoice, $secondTerminal, 20, $user);
    }

    public function test_connection_failure_stays_unknown_and_does_not_post_or_retry(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        $invoice = $this->invoice($user, 'RE-UNKNOWN', 'Unsicher GmbH', 80, 0);
        $terminal = $this->terminal($user, $method);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::Unknown));

        $attempt = $this->app->make(TerminalPaymentService::class)->start($invoice, $terminal, 80, $user);

        $this->assertSame(TerminalPaymentStatus::Unknown, $attempt->status);
        $this->assertNull($attempt->payment_id);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_user_cannot_see_or_use_another_users_invoices_and_terminals(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $method = $this->paymentMethod();
        $invoice = $this->invoice($owner, 'RE-PRIVATE', 'Privat GmbH', 100, 0);
        $terminal = $this->terminal($owner, $method);

        Livewire::actingAs($other)->test(Terminal::class)
            ->assertDontSee('RE-PRIVATE')
            ->call('selectInvoice', $invoice->getKey())
            ->assertNotFound();

        $this->actingAs($other)
            ->get(PaymentTerminalResource::getUrl('edit', ['record' => $terminal]))
            ->assertNotFound();
    }

    private function invoice(User $user, string $number, string $clientName, float $total, float $paid, string $date = '2026-10-20'): Invoice
    {
        $client = Client::query()->create(['client_name' => $clientName, 'client_active' => true]);
        $this->clientIds[] = $client->getKey();
        $invoice = Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice, 'user_id' => $user->getKey(), 'client_id' => $client->getKey(),
            'invoice_group_id' => 1, 'invoice_status_id' => InvoiceStatus::Open, 'is_read_only' => true, 'is_archived' => false,
            'invoice_password' => '', 'invoice_date_created' => $date, 'invoice_time_created' => '10:00:00', 'invoice_date_due' => '2026-11-20',
            'invoice_number' => $number, 'invoice_discount_amount' => 0, 'invoice_discount_percent' => 0, 'invoice_terms' => '',
            'invoice_url_key' => strtolower($number), 'payment_method' => 0, 'creditinvoice_parent_id' => null, 'invoice_einvoicing_version' => '',
        ]);
        $this->invoiceIds[] = $invoice->getKey();
        InvoiceItem::query()->create([
            'invoice_id' => $invoice->getKey(),
            'item_tax_rate_id' => 0,
            'item_tax_included' => false,
            'item_name' => 'Terminal-Testleistung',
            'item_quantity' => 1,
            'item_price' => $total,
            'item_discount_amount' => 0,
            'item_order' => 1,
        ]);
        InvoiceAmount::query()->create([
            'invoice_id' => $invoice->getKey(), 'invoice_sign' => 1, 'invoice_item_subtotal' => $total,
            'invoice_item_tax_total' => 0, 'invoice_tax_total' => 0, 'invoice_total' => $total,
            'invoice_paid' => $paid, 'invoice_balance' => $total - $paid,
        ]);

        return $invoice->load('amount');
    }

    private function paymentMethod(): PaymentMethod
    {
        $method = PaymentMethod::query()->create(['payment_method_name' => 'Karte']);
        $this->paymentMethodIds[] = $method->getKey();

        return $method;
    }

    private function terminal(User $user, PaymentMethod $method): PaymentTerminal
    {
        return PaymentTerminal::query()->create([
            'user_id' => $user->getKey(), 'name' => 'Kasse 1', 'manufacturer' => 'Verifone', 'model' => 'T650c',
            'host' => '192.0.2.10', 'port' => 20007, 'protocol' => 'zvt', 'payment_method_id' => $method->getKey(),
            'is_enabled' => true, 'connection_status' => TerminalConnectionStatus::Online, 'operating_status' => TerminalOperatingStatus::Idle,
        ]);
    }

    protected function tearDown(): void
    {
        Invoice::query()->whereKey($this->invoiceIds)->get()->each->delete();
        Client::query()->whereKey($this->clientIds)->delete();
        PaymentMethod::query()->whereKey($this->paymentMethodIds)->delete();

        parent::tearDown();
    }
}

final class StubTerminalGateway implements PaymentTerminalInterface
{
    public function __construct(private readonly TerminalPaymentStatus $status) {}

    public function mode(): string
    {
        return 'gateway';
    }

    public function start(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult($this->status, 'REF-'.$attempt->getKey());
    }

    public function status(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult($this->status, $attempt->gateway_reference);
    }
}
