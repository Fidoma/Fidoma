<?php

namespace App\Features\Terminals\Models;

use App\Features\Invoices\Models\Invoice;
use App\Features\Payments\Models\Payment;
use App\Features\Terminals\Enums\TerminalPaymentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminalPaymentAttempt extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'invoice_id', 'payment_id', 'amount', 'status',
        'gateway_mode', 'gateway_reference', 'status_message', 'gateway_payload', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => TerminalPaymentStatus::class,
            'gateway_payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }
}
