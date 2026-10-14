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
        Schema::create('ip_quote_tax_rates', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->integer('quote_tax_rate_id', autoIncrement: true);
            $table->integer('quote_id');
            $table->integer('tax_rate_id');
            $table->integer('include_item_tax')->default(0);
            $table->decimal('quote_tax_rate_amount', 10, 2);

            $table->index('quote_id', 'quote_id');
            $table->index('tax_rate_id', 'tax_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_quote_tax_rates');
    }
};
