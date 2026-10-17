<?php

namespace App\Features\Contracts\Models;

use App\Features\Clients\Models\Client;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'template_id',
        'client_id',
        'issuer_id',
        'invoice_group_id',
        'number',
        'contract_create_date',
        'contract_start_date',
        'contract_end_date',
        'contract_location',
        'rendered_content',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'contract_create_date' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
        ];
    }

    /** @return BelongsTo<ContractTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    /** @return BelongsTo<InvoiceGroup, $this> */
    public function invoiceGroup(): BelongsTo
    {
        return $this->belongsTo(InvoiceGroup::class, 'invoice_group_id', 'invoice_group_id');
    }

    /** @return HasMany<ContractFieldValue, $this> */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(ContractFieldValue::class, 'contract_id');
    }
}
