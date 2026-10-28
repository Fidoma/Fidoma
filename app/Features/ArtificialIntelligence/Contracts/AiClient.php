<?php

namespace App\Features\ArtificialIntelligence\Contracts;

interface AiClient
{
    public function configured(): bool;

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function structured(
        string $instructions,
        string $input,
        string $schemaName,
        array $schema,
    ): array;
}
