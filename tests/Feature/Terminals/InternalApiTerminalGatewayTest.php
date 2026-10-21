<?php

namespace Tests\Feature\Terminals;

use App\Features\Invoices\Models\Invoice;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use App\Features\Terminals\Services\InternalApiTerminalGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InternalApiTerminalGatewayTest extends TestCase
{
    public function test_health_reports_the_simulator_as_available_but_explicitly_simulated(): void
    {
        config()->set('terminal.gateway_url', 'http://zvtapi.test');
        config()->set('terminal.gateway_token', 'test-api-key');
        Http::fake([
            'http://zvtapi.test/health/ready' => Http::response([
                'status' => 'ErreichbarNichtRegistriert',
                'betriebsart' => 'simulator',
            ]),
        ]);

        $health = app(InternalApiTerminalGateway::class)->health();

        $this->assertTrue($health->available);
        $this->assertTrue($health->simulated);
        $this->assertSame('ErreichbarNichtRegistriert', $health->status);
    }

    public function test_health_reports_a_reconciliation_lock_as_not_ready(): void
    {
        config()->set('terminal.gateway_url', 'http://zvtapi.test');
        config()->set('terminal.gateway_token', 'test-api-key');
        Http::fake([
            'http://zvtapi.test/health/ready' => Http::response([
                'status' => 'KlaerungErforderlich',
                'betriebsart' => 'production',
            ], 503),
        ]);

        $health = app(InternalApiTerminalGateway::class)->health();

        $this->assertFalse($health->available);
        $this->assertSame('KlaerungErforderlich', $health->status);
    }

    public function test_it_starts_an_asynchronous_zvtapi_payment(): void
    {
        config()->set('terminal.gateway_url', 'http://zvtapi.test');
        config()->set('terminal.gateway_token', 'test-api-key');
        config()->set('terminal.timeout', 130);
        Http::fake([
            'http://zvtapi.test/zahlungen' => Http::response([
                'erfolg' => false,
                'status' => 'Gestartet',
                'betriebsart' => 'production',
                'referenz' => 'attempt-uuid',
            ], 202),
        ]);
        $attempt = new TerminalPaymentAttempt(['id' => 'attempt-uuid', 'amount' => '12.34']);
        $attempt->setRelation('invoice', new Invoice(['invoice_number' => 'RE-2026-0042']));

        $result = app(InternalApiTerminalGateway::class)->start($attempt);

        $this->assertSame(TerminalPaymentStatus::Sending, $result->status);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://zvtapi.test/zahlungen'
            && $request->hasHeader('X-API-Key', 'test-api-key')
            && $request['betrag'] === 12.34
            && $request['referenz'] === 'RE-2026-0042'
            && $request['zahlungsId'] === 'attempt-uuid'
            && $request['bonDrucken'] === false);
    }

    public function test_an_ambiguous_zvtapi_result_remains_unknown(): void
    {
        config()->set('terminal.gateway_url', 'http://zvtapi.test');
        config()->set('terminal.gateway_token', 'test-api-key');
        Http::fake([
            'http://zvtapi.test/zahlungen' => Http::response([
                'erfolg' => false,
                'status' => 'ErgebnisUnbekannt',
                'detail' => 'Zeitueberschreitung',
                'referenz' => 'attempt-uuid',
            ], 502),
        ]);

        $result = app(InternalApiTerminalGateway::class)
            ->start(new TerminalPaymentAttempt(['id' => 'attempt-uuid', 'amount' => '12.34']));

        $this->assertSame(TerminalPaymentStatus::Unknown, $result->status);
        $this->assertSame('Zeitueberschreitung', $result->message);
    }

    public function test_a_terminal_abort_is_mapped_to_cancelled(): void
    {
        config()->set('terminal.gateway_url', 'http://zvtapi.test');
        config()->set('terminal.gateway_token', 'test-api-key');
        Http::fake([
            'http://zvtapi.test/zahlungen' => Http::response([
                'erfolg' => false,
                'status' => 'Abort',
                'referenz' => 'attempt-uuid',
            ], 409),
        ]);

        $result = app(InternalApiTerminalGateway::class)
            ->start(new TerminalPaymentAttempt(['id' => 'attempt-uuid', 'amount' => '12.34']));

        $this->assertSame(TerminalPaymentStatus::Cancelled, $result->status);
    }

    public function test_a_duplicate_reference_requires_manual_review(): void
    {
        config()->set('terminal.gateway_url', 'http://zvtapi.test');
        config()->set('terminal.gateway_token', 'test-api-key');
        Http::fake([
            'http://zvtapi.test/zahlungen' => Http::response([
                'erfolg' => false,
                'status' => 'DoppelteReferenz',
                'referenz' => 'attempt-uuid',
            ], 409),
        ]);

        $result = app(InternalApiTerminalGateway::class)
            ->start(new TerminalPaymentAttempt(['id' => 'attempt-uuid', 'amount' => '12.34']));

        $this->assertSame(TerminalPaymentStatus::Unknown, $result->status);
    }

    public function test_simulated_success_is_never_reported_as_a_real_payment(): void
    {
        config()->set('terminal.gateway_url', 'http://zvtapi.test');
        config()->set('terminal.gateway_token', 'test-api-key');
        Http::fake([
            'http://zvtapi.test/zahlungen/attempt-uuid' => Http::response([
                'erfolg' => true,
                'status' => 'Successful',
                'betriebsart' => 'simulator',
                'simuliert' => true,
                'referenz' => 'attempt-uuid',
                'transaktionsId' => 'SIM-0001',
            ]),
        ]);

        $result = app(InternalApiTerminalGateway::class)
            ->status(new TerminalPaymentAttempt(['id' => 'attempt-uuid', 'amount' => '12.34']));

        $this->assertSame(TerminalPaymentStatus::DemoSuccessful, $result->status);
        $this->assertTrue($result->simulated);
    }
}
