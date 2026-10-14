<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_invoices', function (Blueprint $table) {
            $table->boolean('is_archived')
                ->default(false)
                ->index()
                ->after('is_read_only');
        });
    }

    public function down(): void
    {
        Schema::table('ip_invoices', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });
    }
};
