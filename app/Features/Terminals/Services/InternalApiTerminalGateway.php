<?php

namespace App\Features\Terminals\Services;

use App\Features\Terminals\Contracts\PaymentTerminalInterface;
use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Data\TerminalHealth;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Models\TerminalPaymentAttempt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class InternalApiTerminalGateway implements PaymentTerminalInterface
{
    public function health(): TerminalHealth
    {
        if (blank(config('terminal.gateway_url')) || blank(config('terminal.gateway_token'))) {
            return new TerminalHealth(false, 'NichtKonfiguriert', __('terminals::messages.errors.gateway_not_configured'));
        }

        try {
            $response = Http::baseUrl((string) config('terminal.gateway_url'))
                ->withHeader('X-API-Key', (string) config('terminal.gateway_token'))
                ->acceptJson()
                ->timeout((int) config('terminal.status_timeout', 10))
                ->get('/health/ready');
            $payload = $response->json();
            $status = (string) ($payload['status'] ?? 'Unbekannt');
            $simulated = ($payload['betriebsart'] ?? null) === 'simulator';
            $available = $response->successful() && in_array($status, ['OK', 'ErreichbarNichtRegistriert'], true);

            return new TerminalHealth(
                $available,
                $status,
                $available ? null : __('terminals::messages.errors.terminal_not_ready', ['status' => $status]),
                $simulated,
            );
        } catch (Throwable) {
            return new TerminalHealth(false, 'NichtErreichbar', __('terminals::messages.errors.gateway_unreachable'));
        }
    }

    public function start(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        if (blank(config('terminal.gateway_url')) || blank(config('terminal.gateway_token'))) {
            return new GatewayPaymentResult(TerminalPaymentStatus::Failed, message: __('terminals::messages.errors.gateway_not_configured'));
        }

        try {
            $attempt->loadMissing('invoice');
            $response = Http::baseUrl((string) config('terminal.gateway_url'))
                ->withHeader('X-API-Key', (string) config('terminal.gateway_token'))
                ->acceptJson()
                ->timeout((int) config('terminal.timeout', 130))
                ->post('/zahlungen', [
                    'betrag' => (float) $attempt->amount,
                    'referenz' => (string) $attempt->invoice?->invoice_number,
                    'zahlungsId' => $attempt->getKey(),
                    'bonDrucken' => false,
                ]);

            return $this->fromPayload($response->json(), $response->status());
        } catch (ConnectionException) {
            return new GatewayPaymentResult(TerminalPaymentStatus::Unknown, message: __('terminals::messages.errors.connection_lost'));
        } catch (Throwable) {
            return new GatewayPaymentResult(TerminalPaymentStatus::Failed, message: __('terminals::messages.errors.gateway_rejected'));
        }
    }

    public function status(TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        try {
            $response = Http::baseUrl((string) config('terminal.gateway_url'))
                ->withHeader('X-API-Key', (string) config('terminal.gateway_token'))
                ->acceptJson()
                ->timeout((int) config('terminal.status_timeout', 10))
                ->get('/zahlungen/'.$attempt->getKey());

            if ($response->status() === 404) {
                return new GatewayPaymentResult(TerminalPaymentStatus::Unknown,
                    message: __('terminals::messages.errors.status_unavailable'));
            }

            return $this->fromPayload($response->json(), $response->status());
        } catch (ConnectionException) {
            return new GatewayPaymentResult(TerminalPaymentStatus::Unknown,
                message: __('terminals::messages.errors.connection_lost'));
        } catch (Throwable) {
            return new GatewayPaymentResult(TerminalPaymentStatus::Unknown,
                message: __('terminals::messages.errors.status_unavailable'));
        }
    }

    /** @param array<string, mixed> $payload */
    private function fromPayload(array $payload, int $httpStatus): GatewayPaymentResult
    {
        $apiStatus = (string) ($payload['status'] ?? '');
        $simulated = ($payload['simuliert'] ?? false) === true || ($payload['betriebsart'] ?? null) === 'simulator';
        $status = match (true) {
            ($payload['erfolg'] ?? false) === true && $simulated => TerminalPaymentStatus::DemoSuccessful,
            ($payload['erfolg'] ?? false) === true => TerminalPaymentStatus::Successful,
            $apiStatus === 'Gestartet' => TerminalPaymentStatus::Sending,
            in_array($apiStatus, ['ErgebnisUnbekannt', 'DoppelteReferenz'], true), $httpStatus === 502 => TerminalPaymentStatus::Unknown,
            in_array($apiStatus, ['Abort', 'Abgebrochen'], true) => TerminalPaymentStatus::Cancelled,
            default => TerminalPaymentStatus::Failed,
        };

        return new GatewayPaymentResult(
            $status,
            isset($payload['transaktionsId']) ? (string) $payload['transaktionsId'] : (isset($payload['referenz']) ? (string) $payload['referenz'] : null),
            isset($payload['fehler']) ? (string) $payload['fehler'] : (isset($payload['detail']) ? (string) $payload['detail'] : ($apiStatus ?: null)),
            $payload,
            $simulated,
        );
    }
}
