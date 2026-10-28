<?php

namespace App\Features\ArtificialIntelligence\Services;

use App\Features\ArtificialIntelligence\Contracts\AiClient;
use App\Features\ArtificialIntelligence\Exceptions\AiConfigurationException;
use Illuminate\Contracts\Container\Container;

final class AiClientManager
{
    /** @var array<string, AiClient> */
    private array $clients = [];

    public function __construct(private readonly Container $container) {}

    public function client(?string $engine = null): AiClient
    {
        $engine = strtolower(trim($engine ?? (string) config('ai.engine', 'none')));

        if ($engine === '' || $engine === 'none') {
            return $this->clients['none'] ??= $this->container->make(DisabledAiClient::class);
        }

        return $this->clients[$engine] ??= match ($engine) {
            'openai' => $this->container->make(OpenAiResponsesClient::class),
            default => throw new AiConfigurationException("Die KI-Engine '{$engine}' wird nicht unterstützt."),
        };
    }
}
