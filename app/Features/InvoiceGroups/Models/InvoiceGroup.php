<?php

namespace App\Features\InvoiceGroups\Models;

use App\Features\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceGroup extends Model
{
    protected $table = 'ip_invoice_groups';

    protected $primaryKey = 'invoice_group_id';

    public $timestamps = false;

    protected $fillable = [
        'invoice_group_name',
        'invoice_group_identifier_format',
        'invoice_group_next_id',
        'invoice_group_left_pad',
    ];

    protected function casts(): array
    {
        return [
            'invoice_group_next_id' => 'integer',
            'invoice_group_left_pad' => 'integer',
        ];
    }

    /** @return HasMany<Quote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'invoice_group_id', 'invoice_group_id');
    }
}
