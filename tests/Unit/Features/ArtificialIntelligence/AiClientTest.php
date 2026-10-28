<?php

namespace Tests\Unit\Features\ArtificialIntelligence;

use App\Features\ArtificialIntelligence\Contracts\AiClient;
use App\Features\ArtificialIntelligence\Exceptions\AiConfigurationException;
use App\Features\ArtificialIntelligence\Exceptions\AiResponseException;
use App\Features\ArtificialIntelligence\Services\AiClientManager;
use App\Features\ArtificialIntelligence\Services\DisabledAiClient;
use App\Features\ArtificialIntelligence\Services\OpenAiResponsesClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiClientTest extends TestCase
{
    public function test_disabled_engine_resolves_without_external_configuration(): void
    {
        Config::set('ai.engine', 'none');

        $client = app(AiClient::class);

        $this->assertInstanceOf(DisabledAiClient::class, $client);
        $this->assertFalse($client->configured());
    }

    public function test_manager_rejects_an_unknown_engine(): void
    {
        $this->expectException(AiConfigurationException::class);
        $this->expectExceptionMessage("KI-Engine 'unknown'");

        app(AiClientManager::class)->client('unknown');
    }

    public function test_openai_client_returns_structured_output(): void
    {
        $this->configureOpenAi();
        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => '{"name":"Ada"}',
                    ]],
                ]],
            ]),
        ]);

        $result = app(OpenAiResponsesClient::class)->structured(
            'Extract the requested fields.',
            'Name: Ada',
            'contact_data',
            $this->schema(),
        );

        $this->assertSame(['name' => 'Ada'], $result);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'test-model'
                && $request['text']['format']['type'] === 'json_schema'
                && $request['text']['format']['strict'] === true;
        });
    }

    public function test_openai_client_fails_before_sending_when_configuration_is_missing(): void
    {
        Config::set('ai.api_key', '');
        Config::set('ai.model', '');
        Config::set('ai.endpoint', null);
        Config::set('ai.timeout', 30);
        Http::fake();

        $this->expectException(AiConfigurationException::class);

        try {
            app(OpenAiResponsesClient::class)->structured('Instructions', 'Input', 'test', $this->schema());
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_openai_client_uses_its_default_endpoint_when_no_endpoint_is_configured(): void
    {
        $this->configureOpenAi();
        Config::set('ai.endpoint', null);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'content' => [[
                        'type' => 'output_text',
                        'text' => '{"name":"Ada"}',
                    ]],
                ]],
            ]),
        ]);

        app(OpenAiResponsesClient::class)->structured(
            'Extract the requested fields.',
            'Name: Ada',
            'contact_data',
            $this->schema(),
        );

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/responses');
    }

    public function test_openai_client_rejects_refusals(): void
    {
        $this->configureOpenAi();
        Http::fake([
            '*' => Http::response([
                'output' => [[
                    'content' => [[
                        'type' => 'refusal',
                        'refusal' => 'Unable to process this input.',
                    ]],
                ]],
            ]),
        ]);

        $this->expectException(AiResponseException::class);
        $this->expectExceptionMessage('abgelehnt');

        app(OpenAiResponsesClient::class)->structured('Instructions', 'Input', 'test', $this->schema());
    }

    private function configureOpenAi(): void
    {
        Config::set('ai.engine', 'openai');
        Config::set('ai.endpoint', 'https://api.openai.test/v1/responses');
        Config::set('ai.api_key', 'test-key');
        Config::set('ai.model', 'test-model');
        Config::set('ai.timeout', 30);
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'required' => ['name'],
            'additionalProperties' => false,
        ];
    }
}
