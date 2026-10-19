<?php

namespace App\Features\Projects\Models;

use App\Features\Clients\Models\Client;
use App\Features\Contracts\Models\Contract;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Invoices\Models\Invoice;
use App\Features\Projects\Enums\ProjectStatus;
use App\Features\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'ip_projects';

    protected $primaryKey = 'project_id';

    protected $fillable = [
        'client_id',
        'invoice_group_id',
        'project_number',
        'project_name',
        'project_status',
        'project_description',
        'project_start_date',
        'project_end_date',
        'contract_amount',
        'quote_id',
        'contract_id',
    ];

    protected function casts(): array
    {
        return [
            'project_status' => ProjectStatus::class,
            'project_start_date' => 'date',
            'project_end_date' => 'date',
            'contract_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function invoiceGroup(): BelongsTo
    {
        return $this->belongsTo(InvoiceGroup::class, 'invoice_group_id', 'invoice_group_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'quote_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'project_id', 'project_id');
    }
}
