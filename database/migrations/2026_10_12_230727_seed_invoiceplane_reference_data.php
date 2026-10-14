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
        DB::table('ip_invoice_groups')->insertOrIgnore([
            [
                'invoice_group_id' => 3,
                'invoice_group_name' => 'Rechnung',
                'invoice_group_identifier_format' => '{{{id}}}',
                'invoice_group_next_id' => 1,
                'invoice_group_left_pad' => 0,
            ],
            [
                'invoice_group_id' => 4,
                'invoice_group_name' => 'Angebot',
                'invoice_group_identifier_format' => 'QUO{{{id}}}',
                'invoice_group_next_id' => 1,
                'invoice_group_left_pad' => 0,
            ],
        ]);

        DB::table('ip_payment_methods')->insertOrIgnore([
            ['payment_method_id' => 1, 'payment_method_name' => 'Bar'],
            ['payment_method_id' => 2, 'payment_method_name' => 'Karte'],
            ['payment_method_id' => 3, 'payment_method_name' => 'Überweisung'],
        ]);

        DB::table('ip_tax_rates')->insertOrIgnore([
            'tax_rate_id' => 1,
            'tax_rate_name' => '7%',
            'tax_rate_percent' => 7,
        ]);

        DB::table('ip_units')->insertOrIgnore([
            'unit_id' => 1,
            'unit_name' => 'Nacht',
            'unit_name_plrl' => 'Nächte',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('ip_invoice_groups')->whereIn('invoice_group_id', [3, 4])->delete();
        DB::table('ip_payment_methods')->whereIn('payment_method_id', [1, 2, 3])->delete();
        DB::table('ip_tax_rates')->where('tax_rate_id', 1)->delete();
        DB::table('ip_units')->where('unit_id', 1)->delete();
    }
};
