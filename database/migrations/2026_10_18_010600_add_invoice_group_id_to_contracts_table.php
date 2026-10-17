<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->integer('invoice_group_id')->nullable()->after('issuer_id')->index();
        });

        if (DB::table('contracts')->exists()) {
            $invoiceGroupId = DB::table('ip_invoice_groups')->orderBy('invoice_group_id')->value('invoice_group_id');

            if ($invoiceGroupId === null) {
                throw new LogicException('A number group is required before contracts can be migrated.');
            }

            DB::table('contracts')->whereNull('invoice_group_id')->update(['invoice_group_id' => $invoiceGroupId]);
        }

        Schema::table('contracts', function (Blueprint $table): void {
            $table->integer('invoice_group_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropIndex(['invoice_group_id']);
            $table->dropColumn('invoice_group_id');
        });
    }
};
