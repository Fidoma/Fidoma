<?php

namespace App\Features\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractFieldValue extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = [
        'contract_id',
        'template_field_id',
        'value',
    ];

    /** @return BelongsTo<Contract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /** @return BelongsTo<ContractTemplateField, $this> */
    public function templateField(): BelongsTo
    {
        return $this->belongsTo(ContractTemplateField::class, 'template_field_id');
    }
}
