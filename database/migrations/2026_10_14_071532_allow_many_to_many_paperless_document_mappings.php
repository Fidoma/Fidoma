<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasIndex('ip_bank_transaction_mappings', 'uq_bank_transaction_mapping_document')) {
            Schema::table('ip_bank_transaction_mappings', function (Blueprint $table) {
                $table->dropUnique('uq_bank_transaction_mapping_document');
            });
        }

        if (! Schema::hasIndex('ip_bank_transaction_mappings', 'uq_bank_transaction_mapping_document_transaction')) {
            Schema::table('ip_bank_transaction_mappings', function (Blueprint $table) {
                $table->unique(
                    ['bank_transaction_id', 'document_id'],
                    'uq_bank_transaction_mapping_document_transaction',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('ip_bank_transaction_mappings', 'uq_bank_transaction_mapping_document_transaction')) {
            Schema::table('ip_bank_transaction_mappings', function (Blueprint $table) {
                $table->dropUnique('uq_bank_transaction_mapping_document_transaction');
            });
        }

        if (! Schema::hasIndex('ip_bank_transaction_mappings', 'uq_bank_transaction_mapping_document')) {
            Schema::table('ip_bank_transaction_mappings', function (Blueprint $table) {
                $table->unique('document_id', 'uq_bank_transaction_mapping_document');
            });
        }
    }
};
