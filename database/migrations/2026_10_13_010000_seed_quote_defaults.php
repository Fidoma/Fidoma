<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'quotes_expire_after' => '30',
            'default_quote_group' => '4',
            'default_quote_notes' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (! DB::table('ip_settings')->where('setting_key', $key)->exists()) {
                DB::table('ip_settings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $value,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing InvoicePlane settings are deliberately preserved.
    }
};
