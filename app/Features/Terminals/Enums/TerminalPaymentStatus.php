<?php

namespace App\Features\Terminals\Enums;

enum TerminalPaymentStatus: string
{
    case Ready = 'ready';
    case Sending = 'sending';
    case AwaitingCard = 'awaiting_card';
    case Processing = 'processing';
    case Successful = 'successful';
    case DemoSuccessful = 'demo_successful';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Unknown = 'unknown';

    public function isActive(): bool
    {
        return in_array($this, [self::Sending, self::AwaitingCard, self::Processing], true);
    }

    public function isFinal(): bool
    {
        return ! $this->isActive() && $this !== self::Ready && $this !== self::Unknown;
    }

    public function label(): string
    {
        return __('terminals::messages.payment_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Successful => 'success',
            self::DemoSuccessful, self::Unknown => 'warning',
            self::Cancelled => 'gray',
            self::Failed => 'danger',
            default => 'info',
        };
    }
}
