<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_layouts', function (Blueprint $table): void {
            $table->string('type', 32)->default('invoice')->after('id');
            $table->unique('type');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_layouts', function (Blueprint $table): void {
            $table->dropUnique(['type']);
            $table->dropColumn('type');
        });
    }
};
