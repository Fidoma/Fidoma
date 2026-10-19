<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ip_bank_transaction_mappings', 'payment_created_by_mapping')) {
            Schema::table('ip_bank_transaction_mappings', function (Blueprint $table): void {
                $table->boolean('payment_created_by_mapping')->default(false)->after('payment_id');
            });
        }

        if (Schema::hasColumn('ip_payments', 'payment_external_id')) {
            DB::table('ip_bank_transaction_mappings')
                ->whereIn('payment_id', DB::table('ip_payments')
                    ->select('payment_id')
                    ->where('payment_external_id', 'like', 'bank-transaction:%'))
                ->update(['payment_created_by_mapping' => true]);

            if (Schema::hasIndex('ip_payments', 'idx_payment_external_id')) {
                Schema::table('ip_payments', function (Blueprint $table): void {
                    $table->dropUnique('idx_payment_external_id');
                });
            }

            Schema::table('ip_payments', function (Blueprint $table): void {
                $table->dropColumn('payment_external_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ip_payments', 'payment_external_id')) {
            Schema::table('ip_payments', function (Blueprint $table): void {
                $table->string('payment_external_id')->nullable()->after('payment_id');
                $table->unique('payment_external_id', 'idx_payment_external_id');
            });
        }

        if (Schema::hasColumn('ip_bank_transaction_mappings', 'payment_created_by_mapping')) {
            $mappings = DB::table('ip_bank_transaction_mappings')
                ->where('payment_created_by_mapping', true)
                ->whereNotNull('payment_id')
                ->get(['bank_transaction_id', 'payment_id']);

            foreach ($mappings as $mapping) {
                DB::table('ip_payments')
                    ->where('payment_id', $mapping->payment_id)
                    ->update(['payment_external_id' => 'bank-transaction:'.$mapping->bank_transaction_id]);
            }

            Schema::table('ip_bank_transaction_mappings', function (Blueprint $table): void {
                $table->dropColumn('payment_created_by_mapping');
            });
        }
    }
};
