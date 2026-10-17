<?php

namespace App\Features\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTemplateField extends Model
{
    protected $fillable = [
        'template_id',
        'field_key',
        'label',
        'field_type',
        'is_required',
        'default_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<ContractTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }
}
