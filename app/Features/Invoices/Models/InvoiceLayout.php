<?php

namespace App\Features\Invoices\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLayout extends Model
{
    protected $fillable = ['type', 'html', 'css'];
}
