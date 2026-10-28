<?php

namespace App\Features\ArtificialIntelligence\Queries;

use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Models\AiTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class AvailableAiTemplates
{
    /** @return Collection<int, AiTemplate> */
    public function forUserAndArea(User $user, AiTemplateArea $area): Collection
    {
        return AiTemplate::query()
            ->active()
            ->forArea($area)
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->filter(fn (AiTemplate $template): bool => $user->can('view', $template))
            ->values();
    }

    /** @return array<int, array{id: int, title: string}> */
    public function summariesForUserAndArea(User $user, AiTemplateArea $area): array
    {
        return $this->forUserAndArea($user, $area)
            ->map(fn (AiTemplate $template): array => [
                'id' => (int) $template->getKey(),
                'title' => $template->title,
            ])
            ->all();
    }
}
