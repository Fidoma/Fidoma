<?php

namespace App\Features\Terminals\Contracts;

use App\Features\Terminals\Data\GatewayPaymentResult;
use App\Features\Terminals\Data\TerminalHealth;
use App\Features\Terminals\Models\TerminalPaymentAttempt;

interface PaymentTerminalInterface
{
    public function health(): TerminalHealth;

    public function start(TerminalPaymentAttempt $attempt): GatewayPaymentResult;

    public function status(TerminalPaymentAttempt $attempt): GatewayPaymentResult;
}
