<?php

namespace App\Features\Clients\Models;

use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use App\Features\Projects\Models\Project;
use App\Features\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    protected $table = 'ip_clients';

    protected $primaryKey = 'client_id';

    public const CREATED_AT = 'client_date_created';

    public const UPDATED_AT = 'client_date_modified';

    protected $fillable = [
        'invoice_group_id',
        'client_number',
        'client_name',
        'client_company',
        'client_address_1',
        'client_address_2',
        'client_city',
        'client_state',
        'client_zip',
        'client_country',
        'client_phone',
        'client_fax',
        'client_mobile',
        'client_email',
        'client_web',
        'client_vat_id',
        'client_tax_code',
        'client_language',
        'client_active',
        'client_surname',
        'client_invoicing_contact',
        'client_title',
        'client_einvoicing_active',
        'client_einvoicing_version',
        'client_einvoicing_reference',
        'client_avs',
        'client_insurednumber',
        'client_veka',
        'client_birthdate',
        'client_gender',
    ];

    /** @return BelongsTo<InvoiceGroup, $this> */
    public function invoiceGroup(): BelongsTo
    {
        return $this->belongsTo(InvoiceGroup::class, 'invoice_group_id', 'invoice_group_id');
    }

    protected function casts(): array
    {
        return [
            'client_date_created' => 'datetime',
            'client_date_modified' => 'datetime',
            'client_active' => 'boolean',
            'client_einvoicing_active' => 'boolean',
            'client_birthdate' => 'date',
            'client_gender' => 'integer',
        ];
    }

    /** @return HasMany<Quote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'client_id', 'client_id');
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id', 'client_id');
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id', 'client_id');
    }

    /** @return HasManyThrough<Payment, Invoice, $this> */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Invoice::class,
            'client_id',
            'invoice_id',
            'client_id',
            'invoice_id',
        );
    }
}
