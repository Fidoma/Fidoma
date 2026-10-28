<?php

namespace App\Features\ArtificialIntelligence\Actions;

use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Models\AiTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class PrepareAiTemplateExecution
{
    /**
     * Validates a template selection without invoking an AI provider or changing data.
     *
     * @param  array<string, mixed>  $messageContext
     * @return array{template: AiTemplate, context: array{uid: string, mailbox: string}}
     */
    public function execute(User $user, int $templateId, array $messageContext): array
    {
        $template = AiTemplate::query()
            ->active()
            ->forArea(AiTemplateArea::EmailMessage)
            ->findOrFail($templateId);

        Gate::forUser($user)->authorize('view', $template);

        $context = Validator::make($messageContext, [
            'uid' => ['required', 'string', 'regex:/^\d+$/', 'max:20'],
            'mailbox' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x1F\x7F]/'],
        ])->validate();

        return [
            'template' => $template,
            'context' => [
                'uid' => $context['uid'],
                'mailbox' => $context['mailbox'],
            ],
        ];
    }
}
