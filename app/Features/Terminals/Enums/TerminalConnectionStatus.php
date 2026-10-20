<?php

namespace App\Features\Terminals\Enums;

enum TerminalConnectionStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Error = 'error';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __('terminals::messages.connection_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Online => 'success',
            self::Offline => 'gray',
            self::Error => 'danger',
            self::Unknown => 'warning',
        };
    }
}
