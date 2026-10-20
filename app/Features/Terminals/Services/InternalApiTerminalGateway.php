<?php

namespace App\Features\Terminals\Services;

use App\Features\Terminals\Contracts\PaymentTerminalInterface;
use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Models\PaymentTerminal;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class InternalApiTerminalGateway implements PaymentTerminalInterface
{
    public function mode(): string
    {
        return 'gateway';
    }

    public function start(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        try {
            $response = Http::baseUrl((string) config('terminal.gateway_url'))
                ->withToken((string) config('terminal.gateway_token'))
                ->acceptJson()
                ->timeout((int) config('terminal.timeout', 10))
                ->post('/payments', [
                    'idempotency_key' => $attempt->getKey(),
                    'terminal' => ['host' => $terminal->host, 'port' => $terminal->port, 'protocol' => $terminal->protocol],
                    'amount' => $attempt->amount,
                    'currency' => 'EUR',
                ]);

            if (! $response->successful()) {
                return new GatewayPaymentResult(TerminalPaymentStatus::Failed, message: __('terminals::messages.errors.gateway_rejected'));
            }

            return $this->fromPayload($response->json());
        } catch (ConnectionException) {
            return new GatewayPaymentResult(TerminalPaymentStatus::Unknown, message: __('terminals::messages.errors.connection_lost'));
        }
    }

    public function status(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        try {
            $response = Http::baseUrl((string) config('terminal.gateway_url'))
                ->withToken((string) config('terminal.gateway_token'))
                ->acceptJson()
                ->timeout((int) config('terminal.timeout', 10))
                ->get('/payments/'.$attempt->getKey());

            if (! $response->successful()) {
                return new GatewayPaymentResult(TerminalPaymentStatus::Unknown, message: __('terminals::messages.errors.status_unavailable'));
            }

            return $this->fromPayload($response->json());
        } catch (ConnectionException) {
            return new GatewayPaymentResult(TerminalPaymentStatus::Unknown, message: __('terminals::messages.errors.connection_lost'));
        }
    }

    /** @param array<string, mixed> $payload */
    private function fromPayload(array $payload): GatewayPaymentResult
    {
        $status = TerminalPaymentStatus::tryFrom((string) ($payload['status'] ?? 'unknown')) ?? TerminalPaymentStatus::Unknown;

        return new GatewayPaymentResult(
            $status,
            isset($payload['reference']) ? (string) $payload['reference'] : null,
            isset($payload['message']) ? (string) $payload['message'] : null,
            $payload,
        );
    }
}
