<?php

namespace App\Features\ArtificialIntelligence\Services;

use App\Features\ArtificialIntelligence\Contracts\AiClient;
use App\Features\ArtificialIntelligence\Exceptions\AiConfigurationException;
use App\Features\ArtificialIntelligence\Exceptions\AiResponseException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use JsonException;

final class OpenAiResponsesClient implements AiClient
{
    private const string DEFAULT_ENDPOINT = 'https://api.openai.com/v1/responses';

    public function configured(): bool
    {
        return $this->endpoint() !== null
            && trim((string) config('ai.api_key')) !== ''
            && trim((string) config('ai.model')) !== '';
    }

    public function structured(
        string $instructions,
        string $input,
        string $schemaName,
        array $schema,
    ): array {
        $this->assertConfigured();

        $instructions = trim($instructions);
        $input = trim($input);
        $schemaName = trim($schemaName);

        if ($instructions === '' || $input === '' || preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $schemaName) !== 1) {
            throw new AiConfigurationException('KI-Anweisungen, Eingabe und Schema-Name müssen gültig sein.');
        }

        $payload = $this->request()->post($this->endpoint(), [
            'model' => trim((string) config('ai.model')),
            'instructions' => $instructions,
            'input' => $input,
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ])->throw()->json();

        if (! is_array($payload)) {
            throw new AiResponseException('Die KI-Antwort besitzt ein ungültiges Format.');
        }

        return $this->structuredOutput($payload);
    }

    private function request(): PendingRequest
    {
        return Http::withToken(trim((string) config('ai.api_key')))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout($this->timeout());
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new AiConfigurationException('OpenAI-Endpunkt, API-Key und Modell müssen konfiguriert sein.');
        }
    }

    private function endpoint(): ?string
    {
        $configuredEndpoint = trim((string) config('ai.endpoint'));
        $endpoint = rtrim($configuredEndpoint !== '' ? $configuredEndpoint : self::DEFAULT_ENDPOINT, '/');

        return filter_var($endpoint, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($endpoint, PHP_URL_SCHEME), ['http', 'https'], true)
            ? $endpoint
            : null;
    }

    private function timeout(): int
    {
        return min(120, max(5, (int) config('ai.timeout', 30)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function structuredOutput(array $payload): array
    {
        $outputs = $payload['output'] ?? [];

        if (! is_array($outputs)) {
            throw new AiResponseException('Die KI-Antwort besitzt ein ungültiges Ausgabeformat.');
        }

        foreach ($outputs as $output) {
            if (! is_array($output)) {
                continue;
            }

            $contents = $output['content'] ?? [];

            if (! is_array($contents)) {
                continue;
            }

            foreach ($contents as $content) {
                if (! is_array($content)) {
                    continue;
                }

                if (($content['type'] ?? null) === 'refusal') {
                    throw new AiResponseException('Die KI hat die Verarbeitung abgelehnt.');
                }

                if (($content['type'] ?? null) !== 'output_text' || ! is_string($content['text'] ?? null)) {
                    continue;
                }

                try {
                    $result = json_decode($content['text'], true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new AiResponseException('Die strukturierte KI-Antwort ist ungültig.', previous: $exception);
                }

                if (! is_array($result)) {
                    throw new AiResponseException('Die strukturierte KI-Antwort ist kein Objekt.');
                }

                return $result;
            }
        }

        throw new AiResponseException('Die KI-Antwort enthält keine strukturierten Daten.');
    }
}
