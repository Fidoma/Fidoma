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
        Schema::create('ip_quote_item_amounts', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->integer('item_amount_id', autoIncrement: true);
            $table->integer('item_id');
            $table->decimal('item_subtotal', 10, 2)->default(0);
            $table->decimal('item_tax_total', 10, 2);
            $table->decimal('item_total', 10, 2);

            $table->index('item_id', 'item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_quote_item_amounts');
    }
};
