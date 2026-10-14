<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_quotes', function (Blueprint $table) {
            $table->boolean('is_archived')
                ->default(false)
                ->index()
                ->after('quote_status_id');
        });
    }

    public function down(): void
    {
        Schema::table('ip_quotes', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });
    }
};
