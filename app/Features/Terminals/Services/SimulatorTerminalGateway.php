<?php

namespace App\Features\Terminals\Services;

use App\Features\Terminals\Contracts\PaymentTerminalInterface;
use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Features\Terminals\Models\PaymentTerminal;
use App\Features\Terminals\Models\TerminalPaymentAttempt;

final class SimulatorTerminalGateway implements PaymentTerminalInterface
{
    public function mode(): string
    {
        return 'simulator';
    }

    public function start(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult(
            TerminalPaymentStatus::DemoSuccessful,
            'DEMO-'.$attempt->getKey(),
            __('terminals::messages.demo.not_booked'),
            ['simulated' => true],
        );
    }

    public function status(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult
    {
        return new GatewayPaymentResult(
            TerminalPaymentStatus::DemoSuccessful,
            $attempt->gateway_reference,
            __('terminals::messages.demo.not_booked'),
            ['simulated' => true],
        );
    }
}
