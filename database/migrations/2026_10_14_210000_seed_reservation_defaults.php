<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groupId = DB::table('ip_invoice_groups')
            ->where('invoice_group_name', 'Reservierung')
            ->value('invoice_group_id');

        if (! $groupId) {
            $groupId = DB::table('ip_invoice_groups')->insertGetId([
                'invoice_group_name' => 'Reservierung',
                'invoice_group_identifier_format' => 'RES{{{id}}}',
                'invoice_group_next_id' => 1,
                'invoice_group_left_pad' => 0,
            ]);
        }

        foreach (['default_reservation_group' => (string) $groupId, 'reservations_expire_after' => '30'] as $key => $value) {
            if (! DB::table('ip_settings')->where('setting_key', $key)->exists()) {
                DB::table('ip_settings')->insert(['setting_key' => $key, 'setting_value' => $value]);
            }
        }
    }

    public function down(): void
    {
        // Existing InvoicePlane settings and number groups are deliberately preserved.
    }
};
