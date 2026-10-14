<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_products', function (Blueprint $table): void {
            $table->boolean('product_tax_included')->default(false)->after('product_price');
        });
    }

    public function down(): void
    {
        Schema::table('ip_products', function (Blueprint $table): void {
            $table->dropColumn('product_tax_included');
        });
    }
};
