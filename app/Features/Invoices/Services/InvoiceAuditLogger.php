<?php

namespace App\Features\Invoices\Services;

use App\Features\Invoices\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class InvoiceAuditLogger
{
    public function log(Invoice $invoice, string $event, string $message, string $category = 'finance'): void
    {
        DB::table('ip_audit_logs')->insert([
            'created_at' => now(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? '',
            'category' => $category,
            'event_type' => $event,
            'status' => 'success',
            'entity_type' => 'invoice',
            'entity_id' => $invoice->getKey(),
            'message' => $message,
        ]);
    }
}
