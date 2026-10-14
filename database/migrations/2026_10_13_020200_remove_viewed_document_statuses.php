<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ip_invoices')
            ->where('invoice_status_id', 3)
            ->update(['invoice_status_id' => 2]);

        DB::table('ip_quotes')
            ->where('quote_status_id', 3)
            ->update(['quote_status_id' => 2]);
    }

    public function down(): void
    {
        // Previously viewed documents cannot be distinguished from sent documents after migration.
    }
};
