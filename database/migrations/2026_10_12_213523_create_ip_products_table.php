<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ip_products', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->integer('product_id', autoIncrement: true);
            $table->integer('family_id');
            $table->string('product_number', 15);
            $table->string('product_name', 50);
            $table->longText('product_description');
            $table->float('product_price', 10);
            $table->float('purchase_price', 10);
            $table->integer('tax_rate_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ip_products MODIFY product_price FLOAT(10, 2) NOT NULL');
            DB::statement('ALTER TABLE ip_products MODIFY purchase_price FLOAT(10, 2) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_products');
    }
};
