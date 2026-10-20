<?php

namespace App\Features\Terminals\Contracts;

use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Models\PaymentTerminal;
use App\Features\Terminals\Models\TerminalPaymentAttempt;

interface PaymentTerminalInterface
{
    public function mode(): string;

    public function start(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult;

    public function status(PaymentTerminal $terminal, TerminalPaymentAttempt $attempt): GatewayPaymentResult;
}
