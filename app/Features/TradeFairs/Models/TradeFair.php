<?php

namespace App\Features\TradeFairs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TradeFair extends Model
{
    protected $fillable = [
        'identity_key',
        'name',
        'description',
        'start_date',
        'end_date',
        'location',
        'country',
        'website',
        'is_local',
        'is_active',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_local' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<TradeFair> $query */
    public function scopeVisibleInScheduler(Builder $query): void
    {
        $query->where('is_active', true)->where('is_local', true);
    }
}
