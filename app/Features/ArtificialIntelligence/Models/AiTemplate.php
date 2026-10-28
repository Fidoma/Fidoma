<?php

namespace App\Features\ArtificialIntelligence\Models;

use App\Features\ArtificialIntelligence\Enums\AiTemplateArea;
use App\Features\ArtificialIntelligence\Policies\AiTemplatePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[UsePolicy(AiTemplatePolicy::class)]
class AiTemplate extends Model
{
    protected $fillable = [
        'title',
        'area',
        'prompt',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'area' => AiTemplateArea::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeForArea(Builder $query, AiTemplateArea $area): void
    {
        $query->where('area', $area->value);
    }
}
