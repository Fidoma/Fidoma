<?php

namespace App\Features\Terminals\Data;

final readonly class TerminalHealth
{
    public function __construct(
        public bool $available,
        public string $status,
        public ?string $message = null,
        public bool $simulated = false,
    ) {}
}
