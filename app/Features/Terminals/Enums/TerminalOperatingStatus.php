<?php

namespace App\Features\Terminals\Enums;

enum TerminalOperatingStatus: string
{
    case Idle = 'idle';
    case Busy = 'busy';
    case Error = 'error';

    public function label(): string
    {
        return __('terminals::messages.operating_status.'.$this->value);
    }
}
