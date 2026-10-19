<?php

namespace App\Features\Invoices\Models;

use App\Features\BankTransactions\Models\BankTransactionMapping;
use App\Features\Clients\Models\Client;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Invoices\Enums\InvoiceBillingType;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\PaymentMethods\Models\PaymentMethod;
use App\Features\Payments\Models\Payment;
use App\Features\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class Invoice extends Model
{
    protected $table = 'ip_invoices';

    protected $primaryKey = 'invoice_id';

    public const CREATED_AT = 'invoice_date_created';

    public const UPDATED_AT = 'invoice_date_modified';

    protected $fillable = [
        'invoice_type', 'billing_type', 'user_id', 'client_id', 'project_id', 'invoice_group_id', 'invoice_status_id', 'is_read_only', 'is_archived',
        'invoice_password', 'invoice_date_created', 'invoice_time_created', 'invoice_date_due',
        'invoice_number', 'invoice_discount_amount', 'invoice_discount_percent', 'invoice_terms',
        'invoice_url_key', 'payment_method', 'creditinvoice_parent_id', 'invoice_einvoicing_version',
    ];

    protected function casts(): array
    {
        return [
            'invoice_type' => InvoiceType::class,
            'billing_type' => InvoiceBillingType::class,
            'invoice_status_id' => InvoiceStatus::class,
            'is_read_only' => 'boolean',
            'is_archived' => 'boolean',
            'invoice_date_created' => 'date',
            'invoice_date_modified' => 'datetime',
            'invoice_date_due' => 'date',
            'invoice_discount_amount' => 'decimal:2',
            'invoice_discount_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice): void {
            $invoice->invoice_terms ??= '';

            if ($invoice->invoice_status_id !== InvoiceStatus::Draft && blank($invoice->invoice_number)) {
                throw new LogicException('An issued invoice must have an invoice number.');
            }
        });

        static::deleting(function (Invoice $invoice): void {
            $invoice->items()->get()->each->delete();
            $invoice->invoiceTaxRates()->delete();
            $invoice->payments()->delete();
            $invoice->amount()->delete();
        });
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<InvoiceGroup, $this> */
    public function invoiceGroup(): BelongsTo
    {
        return $this->belongsTo(InvoiceGroup::class, 'invoice_group_id', 'invoice_group_id');
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'payment_method_id');
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'invoice_id')->orderBy('item_order');
    }

    /** @return HasMany<InvoiceTaxRate, $this> */
    public function invoiceTaxRates(): HasMany
    {
        return $this->hasMany(InvoiceTaxRate::class, 'invoice_id', 'invoice_id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
    }

    /** @return HasManyThrough<BankTransactionMapping, Payment, $this> */
    public function bankTransactionMappings(): HasManyThrough
    {
        return $this->hasManyThrough(
            BankTransactionMapping::class,
            Payment::class,
            'invoice_id',
            'payment_id',
            'invoice_id',
            'payment_id',
        );
    }

    /** @return HasOne<InvoiceAmount, $this> */
    public function amount(): HasOne
    {
        return $this->hasOne(InvoiceAmount::class, 'invoice_id', 'invoice_id');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'creditinvoice_parent_id', 'invoice_id');
    }

    public function isOverdue(): bool
    {
        return $this->invoice_status_id === InvoiceStatus::Open
            && $this->invoice_date_due->isPast()
            && (float) ($this->amount?->invoice_balance ?? 0) > 0;
    }
}
