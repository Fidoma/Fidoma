<?php

namespace App\Features\Quotes\Enums;

enum QuoteStatus: int
{
    case Draft = 1;
    case Sent = 2;
    case Approved = 4;
    case Rejected = 5;
    case Canceled = 6;

    public function label(): string
    {
        return __('quotes::messages.statuses.'.strtolower($this->name));
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Canceled => 'warning',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
