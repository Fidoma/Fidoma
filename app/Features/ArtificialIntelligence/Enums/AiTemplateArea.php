<?php

namespace App\Features\ArtificialIntelligence\Enums;

enum AiTemplateArea: string
{
    case EmailMessage = 'email.message';

    public function label(): string
    {
        return match ($this) {
            self::EmailMessage => __('artificial-intelligence::messages.areas.email_message'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $area): array => [$area->value => $area->label()])
            ->all();
    }
}
