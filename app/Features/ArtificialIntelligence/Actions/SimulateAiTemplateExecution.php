<?php

namespace App\Features\ArtificialIntelligence\Actions;

use App\Features\ArtificialIntelligence\Enums\AiTemplateAction;
use App\Features\ArtificialIntelligence\Services\AiClientManager;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SimulateAiTemplateExecution
{
    public function __construct(
        private readonly PrepareAiTemplateExecution $prepare,
        private readonly AiClientManager $clients,
    ) {}

    /**
     * @param  array<string, mixed>  $messageContext
     * @return array{action: AiTemplateAction, data: array<string, mixed>, template_title: string}
     */
    public function execute(User $user, int $templateId, array $messageContext): array
    {
        $selection = $this->prepare->execute($user, $templateId, $messageContext);
        $template = $selection['template'];
        $action = $template->action;

        if (! $action instanceof AiTemplateAction) {
            throw ValidationException::withMessages([
                'template' => __('artificial-intelligence::messages.fields.action'),
            ]);
        }

        $data = $this->clients->client('fake')->structured(
            $template->prompt,
            $this->simulatedMessage($action),
            $action->schemaName(),
            $this->schema($action),
        );

        $validated = Validator::make($data, $this->rules($action))->validate();

        Log::info('Simulated AI template action prepared.', [
            'template_id' => $template->getKey(),
            'action' => $action->value,
            'mailbox' => $selection['context']['mailbox'],
            'message_uid' => $selection['context']['uid'],
            'engine' => 'fake',
        ]);

        return [
            'action' => $action,
            'data' => $validated,
            'template_title' => $template->title,
        ];
    }

    private function simulatedMessage(AiTemplateAction $action): string
    {
        return match ($action) {
            AiTemplateAction::CreateClient => 'Bitte legen Sie Max Mustermann aus Berlin als neuen Kunden an. E-Mail: max.mustermann@example.test.',
            AiTemplateAction::CreateReservation => 'Max Mustermann möchte nächste Woche für drei Nächte Zimmer 101 reservieren.',
        };
    }

    /** @return array<string, mixed> */
    private function schema(AiTemplateAction $action): array
    {
        $properties = match ($action) {
            AiTemplateAction::CreateClient => [
                'client_company', 'client_email', 'client_phone', 'client_address_1', 'client_zip',
                'client_city', 'client_country', 'client_active',
            ],
            AiTemplateAction::CreateReservation => [
                'client_name', 'client_email', 'arrival_date', 'departure_date', 'room_number', 'product_name',
            ],
        };

        return [
            'type' => 'object',
            'properties' => collect($properties)->mapWithKeys(fn (string $field): array => [
                $field => ['type' => $field === 'client_active' ? 'boolean' : ['string', 'null']],
            ])->all(),
            'required' => $properties,
            'additionalProperties' => false,
        ];
    }

    /** @return array<string, list<string>> */
    private function rules(AiTemplateAction $action): array
    {
        return match ($action) {
            AiTemplateAction::CreateClient => [
                'client_company' => ['required', 'string', 'max:150'],
                'client_email' => ['nullable', 'email', 'max:100'],
                'client_phone' => ['nullable', 'string', 'max:20'],
                'client_address_1' => ['nullable', 'string', 'max:100'],
                'client_zip' => ['nullable', 'string', 'max:15'],
                'client_city' => ['nullable', 'string', 'max:45'],
                'client_country' => ['nullable', 'string', 'max:35'],
                'client_active' => ['required', 'boolean'],
            ],
            AiTemplateAction::CreateReservation => [
                'client_name' => ['nullable', 'string', 'max:255'],
                'client_email' => ['nullable', 'email', 'max:100'],
                'arrival_date' => ['required', 'date_format:Y-m-d'],
                'departure_date' => ['required', 'date_format:Y-m-d', 'after:arrival_date'],
                'room_number' => ['nullable', 'string', 'max:50'],
                'product_name' => ['nullable', 'string', 'max:255'],
            ],
        };
    }
}
