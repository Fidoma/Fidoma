<?php

namespace App\Features\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractTemplate extends Model
{
    protected $fillable = [
        'name',
        'content',
        'status',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    /** @return HasMany<ContractTemplateField, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(ContractTemplateField::class, 'template_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @return HasMany<Contract, $this> */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'template_id');
    }
}
