<?php

namespace App\Features\ArtificialIntelligence\Services;

use App\Features\ArtificialIntelligence\Contracts\AiClient;
use App\Features\ArtificialIntelligence\Exceptions\AiConfigurationException;

final class DisabledAiClient implements AiClient
{
    public function configured(): bool
    {
        return false;
    }

    public function structured(
        string $instructions,
        string $input,
        string $schemaName,
        array $schema,
    ): array {
        throw new AiConfigurationException('Es ist keine KI-Engine konfiguriert.');
    }
}
