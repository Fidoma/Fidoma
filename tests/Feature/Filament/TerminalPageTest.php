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
use App\Features\Terminals\Data\TerminalHealth;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Filament\Pages\Terminal;
use App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts\Pages\ListTerminalPaymentAttempts;
use App\Features\Terminals\Filament\Resources\TerminalPaymentAttempts\TerminalPaymentAttemptResource;
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
            ->assertSee('150,00')
            ->assertDontSee('Terminal auswählen');

        $this->assertSame('100.00', $open->amount->invoice_balance);
    }

    public function test_global_history_is_visible_without_selecting_an_invoice_and_is_user_scoped(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $firstInvoice = $this->invoice($user, 'RE-HISTORY-1', 'Historie Eins', 100, 0);
        $secondInvoice = $this->invoice($user, 'RE-HISTORY-2', 'Historie Zwei', 200, 0);
        $foreignInvoice = $this->invoice($other, 'RE-HISTORY-FOREIGN', 'Fremde Historie', 300, 0);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::Failed));

        foreach ([$firstInvoice, $secondInvoice] as $invoice) {
            TerminalPaymentAttempt::query()->create([
                'user_id' => $user->getKey(),
                'invoice_id' => $invoice->getKey(),
                'amount' => '10.00',
                'status' => TerminalPaymentStatus::Failed,
                'gateway_mode' => 'zvtapi',
                'status_message' => 'Teststatus',
                'started_at' => now(),
                'completed_at' => now(),
            ]);
        }
        TerminalPaymentAttempt::query()->create([
            'user_id' => $other->getKey(),
            'invoice_id' => $foreignInvoice->getKey(),
            'amount' => '10.00',
            'status' => TerminalPaymentStatus::Failed,
            'gateway_mode' => 'zvtapi',
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        Invoice::query()->whereKey([$firstInvoice->getKey(), $secondInvoice->getKey(), $foreignInvoice->getKey()])
            ->update(['is_archived' => true]);

        Livewire::actingAs($user)->test(Terminal::class)
            ->assertSet('selectedInvoiceId', null)
            ->assertSee('Die letzten Terminal-Zahlungsversuche für alle Rechnungen.')
            ->assertSee('RE-HISTORY-1')
            ->assertSee('RE-HISTORY-2')
            ->assertDontSee('RE-HISTORY-FOREIGN');
    }

    public function test_payment_history_resource_is_read_only_and_user_scoped(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownInvoice = $this->invoice($user, 'RE-RESOURCE-OWN', 'Eigener Kunde', 100, 0);
        $foreignInvoice = $this->invoice($other, 'RE-RESOURCE-FOREIGN', 'Fremder Kunde', 100, 0);
        $ownAttempt = $this->attempt($user, $ownInvoice, TerminalPaymentStatus::Successful);
        $foreignAttempt = $this->attempt($other, $foreignInvoice, TerminalPaymentStatus::Failed);

        $this->assertFalse(TerminalPaymentAttemptResource::canCreate());
        $this->assertSame(['index'], array_keys(TerminalPaymentAttemptResource::getPages()));

        Livewire::actingAs($user)->test(ListTerminalPaymentAttempts::class)
            ->assertCanSeeTableRecords([$ownAttempt])
            ->assertCanNotSeeTableRecords([$foreignAttempt])
            ->assertSee('RE-RESOURCE-OWN')
            ->assertDontSee('RE-RESOURCE-FOREIGN');
    }

    public function test_payment_history_resource_can_be_filtered_by_status_and_date(): void
    {
        $user = User::factory()->create();
        $invoice = $this->invoice($user, 'RE-RESOURCE-FILTER', 'Filterkunde', 100, 0);
        $successful = $this->attempt($user, $invoice, TerminalPaymentStatus::Successful, '2026-10-10 12:00:00');
        $failed = $this->attempt($user, $invoice, TerminalPaymentStatus::Failed, '2026-10-20 12:00:00');

        Livewire::actingAs($user)->test(ListTerminalPaymentAttempts::class)
            ->filterTable('status', TerminalPaymentStatus::Failed->value)
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$successful])
            ->removeTableFilter('status')
            ->filterTable('started_at', ['from' => '2026-10-09', 'until' => '2026-10-11'])
            ->assertCanSeeTableRecords([$successful])
            ->assertCanNotSeeTableRecords([$failed]);
    }

    public function test_confirmed_partial_payment_uses_configured_payment_method_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        config()->set('terminal.payment_method_id', $method->getKey());
        $invoice = $this->invoice($user, 'RE-CARD', 'Kartenkunde', 100, 0);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::Successful));
        $service = $this->app->make(TerminalPaymentService::class);

        $attempt = $service->start($invoice, '40.00', $user);

        $this->assertNotNull($attempt->refresh()->payment_id);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertDatabaseHas('ip_payments', ['invoice_id' => $invoice->getKey(), 'payment_method_id' => $method->getKey(), 'payment_amount' => 40]);
        $this->assertSame('60.00', $invoice->refresh()->amount->invoice_balance);
        $this->assertSame(InvoiceStatus::Open, $invoice->invoice_status_id);
    }

    public function test_simulated_success_is_recorded_but_never_posted_to_the_invoice(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        config()->set('terminal.payment_method_id', $method->getKey());
        $invoice = $this->invoice($user, 'RE-DEMO', 'Demo GmbH', 100, 0);
        $this->app->instance(PaymentTerminalInterface::class,
            new StubTerminalGateway(TerminalPaymentStatus::DemoSuccessful));

        $attempt = $this->app->make(TerminalPaymentService::class)->start($invoice, '40.00', $user);

        $this->assertSame(TerminalPaymentStatus::DemoSuccessful, $attempt->status);
        $this->assertNull($attempt->payment_id);
        $this->assertSame(0, $invoice->payments()->count());
        $this->assertSame('100.00', $invoice->refresh()->amount->invoice_balance);
    }

    public function test_a_reused_gateway_transaction_id_requires_review_instead_of_causing_a_constraint_error(): void
    {
        $user = User::factory()->create();
        $firstInvoice = $this->invoice($user, 'RE-FIRST-TX', 'Erste GmbH', 100, 0);
        $secondInvoice = $this->invoice($user, 'RE-SECOND-TX', 'Zweite GmbH', 100, 0);
        $this->app->instance(PaymentTerminalInterface::class, new FixedReferenceStubTerminalGateway);
        $service = $this->app->make(TerminalPaymentService::class);

        $first = $service->start($firstInvoice, '20.00', $user);
        $second = $service->start($secondInvoice, '20.00', $user);

        $this->assertSame(TerminalPaymentStatus::DemoSuccessful, $first->status);
        $this->assertSame(TerminalPaymentStatus::Unknown, $second->status);
        $this->assertStringContainsString('bereits verwendet', (string) $second->status_message);
        $this->assertNotSame($first->gateway_reference, $second->gateway_reference);
    }

    public function test_gateway_is_locked_globally_while_a_payment_is_active(): void
    {
        $user = User::factory()->create();
        $firstInvoice = $this->invoice($user, 'RE-1', 'Kunde 1', 100, 0);
        $secondInvoice = $this->invoice($user, 'RE-2', 'Kunde 2', 100, 0);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::AwaitingCard));
        $service = $this->app->make(TerminalPaymentService::class);
        $service->start($firstInvoice, '20.00', $user);

        $this->expectException(ValidationException::class);
        $service->start($secondInvoice, '20.00', $user);
    }

    public function test_page_shows_a_global_unresolved_attempt_instead_of_ready(): void
    {
        $user = User::factory()->create();
        $invoice = $this->invoice($user, 'RE-BLOCKED', 'Gesperrt GmbH', 100, 0);
        $blockingInvoice = $this->invoice($user, 'RE-UNKNOWN-BLOCKER', 'Prüfung GmbH', 50, 0);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::AwaitingCard));
        TerminalPaymentAttempt::query()->create([
            'user_id' => $user->getKey(),
            'invoice_id' => $blockingInvoice->getKey(),
            'amount' => '50.00',
            'status' => TerminalPaymentStatus::Unknown,
            'gateway_mode' => 'zvtapi',
            'started_at' => now(),
        ]);

        Livewire::actingAs($user)->test(Terminal::class)
            ->call('selectInvoice', $invoice->getKey())
            ->assertSee('Nicht bereit')
            ->assertSee('noch aktiv oder ungeklärt');
    }

    public function test_invoice_is_locked_while_a_payment_is_active(): void
    {
        $user = User::factory()->create();
        $invoice = $this->invoice($user, 'RE-DUPLICATE', 'Kunde', 100, 0);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::AwaitingCard));
        $service = $this->app->make(TerminalPaymentService::class);
        $service->start($invoice, '20.00', $user);

        $this->expectException(ValidationException::class);
        $service->start($invoice, '20.00', $user);
    }

    public function test_connection_failure_stays_unknown_and_does_not_post_or_retry(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        config()->set('terminal.payment_method_id', $method->getKey());
        $invoice = $this->invoice($user, 'RE-UNKNOWN', 'Unsicher GmbH', 80, 0);
        $gateway = new StubTerminalGateway(TerminalPaymentStatus::Unknown);
        $this->app->instance(PaymentTerminalInterface::class, $gateway);

        $attempt = $this->app->make(TerminalPaymentService::class)->start($invoice, '80.00', $user);

        $this->assertSame(TerminalPaymentStatus::Unknown, $attempt->status);
        $this->assertNull($attempt->payment_id);
        $this->assertSame(1, $gateway->calls);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_success_without_configured_payment_method_requires_review(): void
    {
        $user = User::factory()->create();
        config()->set('terminal.payment_method_id');
        $invoice = $this->invoice($user, 'RE-CONFIG', 'Konfiguration GmbH', 80, 0);
        $this->app->instance(PaymentTerminalInterface::class, new StubTerminalGateway(TerminalPaymentStatus::Successful));

        $attempt = $this->app->make(TerminalPaymentService::class)->start($invoice, '80.00', $user);

        $this->assertSame(TerminalPaymentStatus::Unknown, $attempt->status);
        $this->assertNull($attempt->payment_id);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_reconciled_unknown_payment_is_posted_only_after_a_confirmed_status_check(): void
    {
        $user = User::factory()->create();
        $method = $this->paymentMethod();
        config()->set('terminal.payment_method_id', $method->getKey());
        $invoice = $this->invoice($user, 'RE-RECONCILE', 'Klärung GmbH', 80, 0);
        $this->app->instance(PaymentTerminalInterface::class, new ResolvingStubTerminalGateway);
        $service = $this->app->make(TerminalPaymentService::class);

        $attempt = $service->start($invoice, '80.00', $user);
        $this->assertSame(TerminalPaymentStatus::Unknown, $attempt->status);
        $this->assertSame(0, $invoice->payments()->count());

        $resolved = $service->refresh($attempt, $user);

        $this->assertSame(TerminalPaymentStatus::Successful, $resolved->status);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame('0.00', $invoice->refresh()->amount->invoice_balance);
    }

    public function test_user_cannot_see_or_use_another_users_invoice(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $invoice = $this->invoice($owner, 'RE-PRIVATE', 'Privat GmbH', 100, 0);

        Livewire::actingAs($other)->test(Terminal::class)
            ->assertDontSee('RE-PRIVATE')
            ->call('selectInvoice', $invoice->getKey())
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
            'invoice_id' => $invoice->getKey(), 'item_tax_rate_id' => 0, 'item_tax_included' => false,
            'item_name' => 'Terminal-Testleistung', 'item_quantity' => 1, 'item_price' => $total, 'item_discount_amount' => 0, 'item_order' => 1,
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

    private function attempt(
        User $user,
        Invoice $invoice,
        TerminalPaymentStatus $status,
        string $startedAt = '2026-10-20 12:00:00',
    ): TerminalPaymentAttempt {
        return TerminalPaymentAttempt::query()->create([
            'user_id' => $user->getKey(),
            'invoice_id' => $invoice->getKey(),
            'amount' => '25.00',
            'status' => $status,
            'gateway_mode' => 'zvtapi',
            'gateway_reference' => 'REF-'.str()->uuid(),
            'started_at' => $startedAt,
            'completed_at' => $startedAt,
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
    public int $calls = 0;

    public function __construct(private readonly TerminalPaymentStatus $status) {}

    public function health(): TerminalHealth
    {
        return new TerminalHealth(true, 'OK');
    }

    public function start(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        $this->calls++;

        return new GatewayPaymentResult($this->status, 'REF-'.$attempt->getKey());
    }

    public function status(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult($this->status, $attempt->gateway_reference);
    }
}

final class ResolvingStubTerminalGateway implements PaymentTerminalInterface
{
    public function health(): TerminalHealth
    {
        return new TerminalHealth(true, 'OK');
    }

    public function start(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult(TerminalPaymentStatus::Unknown, 'REF-'.$attempt->getKey());
    }

    public function status(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult(TerminalPaymentStatus::Successful, 'TX-RECONCILED');
    }
}

final class FixedReferenceStubTerminalGateway implements PaymentTerminalInterface
{
    public function health(): TerminalHealth
    {
        return new TerminalHealth(true, 'OK', simulated: true);
    }

    public function start(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult(TerminalPaymentStatus::DemoSuccessful, 'SIM-FIXED', simulated: true);
    }

    public function status(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return $this->start($attempt);
    }
}
