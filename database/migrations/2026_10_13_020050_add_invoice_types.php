<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_invoices', function (Blueprint $table): void {
            $table->string('invoice_type', 20)->default('invoice')->after('invoice_id')->index();
            $table->index('creditinvoice_parent_id');
        });

        $groups = [
            ['name' => 'Gutschrift', 'format' => 'GS{{{id}}}'],
            ['name' => 'Stornorechnung', 'format' => 'ST{{{id}}}'],
        ];

        foreach ($groups as $group) {
            if (! DB::table('ip_invoice_groups')->where('invoice_group_name', $group['name'])->exists()) {
                DB::table('ip_invoice_groups')->insert([
                    'invoice_group_name' => $group['name'],
                    'invoice_group_identifier_format' => $group['format'],
                    'invoice_group_next_id' => 1,
                    'invoice_group_left_pad' => 0,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ip_invoices', function (Blueprint $table): void {
            $table->dropIndex(['creditinvoice_parent_id']);
            $table->dropColumn('invoice_type');
        });
    }
};
