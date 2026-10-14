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
        Schema::create('ip_quotes', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->integer('quote_id', autoIncrement: true);
            $table->integer('invoice_id')->default(0);
            $table->integer('user_id');
            $table->integer('client_id');
            $table->integer('invoice_group_id');
            $table->tinyInteger('quote_status_id')->default(1);
            $table->date('quote_date_created');
            $table->dateTime('quote_date_modified');
            $table->date('quote_date_expires');
            $table->string('quote_number', 20);
            $table->char('quote_url_key', 32);

            $table->index(
                ['user_id', 'client_id', 'invoice_group_id', 'quote_date_created', 'quote_date_expires', 'quote_number'],
                'user_id',
            );
            $table->index('invoice_id', 'invoice_id');
            $table->index('quote_status_id', 'quote_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_quotes');
    }
};
