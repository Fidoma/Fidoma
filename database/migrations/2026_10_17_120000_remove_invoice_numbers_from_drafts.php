<?php

use App\Features\Invoices\Enums\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ip_invoices')
            ->where('invoice_status_id', InvoiceStatus::Draft->value)
            ->update(['invoice_number' => null]);
    }

    public function down(): void
    {
        // Previously consumed invoice numbers cannot be restored safely.
    }
};
