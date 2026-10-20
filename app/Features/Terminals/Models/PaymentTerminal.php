<?php

namespace App\Features\Terminals\Models;

use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Terminals\Enums\TerminalConnectionStatus;
use App\Features\Terminals\Enums\TerminalOperatingStatus;
use App\Features\Terminals\Policies\PaymentTerminalPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(PaymentTerminalPolicy::class)]
class PaymentTerminal extends Model
{
    protected $fillable = [
        'user_id', 'name', 'manufacturer', 'model', 'host', 'port', 'protocol',
        'payment_method_id', 'is_enabled', 'connection_status', 'operating_status', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'connection_status' => TerminalConnectionStatus::class,
            'operating_status' => TerminalOperatingStatus::class,
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(TerminalPaymentAttempt::class);
    }
}
