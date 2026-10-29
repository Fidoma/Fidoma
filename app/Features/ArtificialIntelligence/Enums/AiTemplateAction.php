<?php

namespace App\Features\ArtificialIntelligence\Enums;

enum AiTemplateAction: string
{
    case CreateClient = 'client.create';
    case CreateReservation = 'reservation.create';

    public function label(): string
    {
        return match ($this) {
            self::CreateClient => __('artificial-intelligence::messages.actions.create_client'),
            self::CreateReservation => __('artificial-intelligence::messages.actions.create_reservation'),
        };
    }

    public function schemaName(): string
    {
        return str_replace('.', '_', $this->value);
    }

    public function modalAction(): string
    {
        return match ($this) {
            self::CreateClient => 'createClientFromAi',
            self::CreateReservation => 'createReservationFromAi',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $action): array => [$action->value => $action->label()])
            ->all();
    }
}
