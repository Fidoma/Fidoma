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
        Schema::create('ip_quote_items', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->integer('item_id', autoIncrement: true);
            $table->integer('quote_id');
            $table->integer('item_tax_rate_id');
            $table->date('item_date_added');
            $table->string('item_name', 100);
            $table->longText('item_description');
            $table->decimal('item_quantity', 10, 2);
            $table->decimal('item_price', 10, 2);
            $table->integer('item_order')->default(0);

            $table->index(['quote_id', 'item_date_added', 'item_order'], 'quote_id');
            $table->index('item_tax_rate_id', 'item_tax_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_quote_items');
    }
};
