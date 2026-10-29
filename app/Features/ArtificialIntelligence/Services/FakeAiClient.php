<?php

namespace App\Features\ArtificialIntelligence\Services;

use App\Features\ArtificialIntelligence\Contracts\AiClient;
use App\Features\ArtificialIntelligence\Exceptions\AiResponseException;

final class FakeAiClient implements AiClient
{
    public function configured(): bool
    {
        return true;
    }

    public function structured(
        string $instructions,
        string $input,
        string $schemaName,
        array $schema,
    ): array {
        return match ($schemaName) {
            'client_create' => [
                'client_title' => 'Herr',
                'client_name' => 'Max',
                'client_surname' => 'Mustermann',
                'client_company' => null,
                'client_email' => 'max.mustermann@example.test',
                'client_phone' => '+49 30 12345678',
                'client_address_1' => 'Musterstraße 12',
                'client_zip' => '10115',
                'client_city' => 'Berlin',
                'client_country' => 'Deutschland',
                'client_active' => true,
            ],
            'reservation_create' => [
                'client_name' => 'Max Mustermann',
                'client_email' => 'max.mustermann@example.test',
                'arrival_date' => now()->addWeek()->toDateString(),
                'departure_date' => now()->addWeek()->addDays(3)->toDateString(),
                'room_number' => '101',
                'product_name' => 'Übernachtung',
            ],
            default => throw new AiResponseException("Für das Schema '{$schemaName}' existiert keine simulierte KI-Antwort."),
        };
    }
}
