<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'invoices_due_after' => '30',
            'default_invoice_group' => '3',
            'default_invoice_terms' => '',
            'invoice_default_payment_method' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (! DB::table('ip_settings')->where('setting_key', $key)->exists()) {
                DB::table('ip_settings')->insert(['setting_key' => $key, 'setting_value' => $value]);
            }
        }
    }

    public function down(): void
    {
        // Existing InvoicePlane settings are deliberately preserved.
    }
};
