<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_products', function (Blueprint $table): void {
            $table->longText('product_description')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('ip_products')->whereNull('product_description')->update(['product_description' => '']);

        Schema::table('ip_products', function (Blueprint $table): void {
            $table->longText('product_description')->nullable(false)->change();
        });
    }
};
