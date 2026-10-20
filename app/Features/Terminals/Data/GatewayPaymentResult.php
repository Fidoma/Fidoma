<?php

namespace App\Features\Terminals\Data;

use App\Features\Terminals\Enums\TerminalPaymentStatus;

final readonly class GatewayPaymentResult
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public TerminalPaymentStatus $status,
        public ?string $reference = null,
        public ?string $message = null,
        public array $payload = [],
    ) {}
}
