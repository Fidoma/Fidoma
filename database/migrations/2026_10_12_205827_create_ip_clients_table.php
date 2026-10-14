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
        Schema::create('ip_clients', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->integer('client_id', autoIncrement: true);
            $table->dateTime('client_date_created');
            $table->dateTime('client_date_modified');
            $table->string('client_name', 100);
            $table->string('client_address_1', 100)->nullable()->default('');
            $table->string('client_city', 45)->nullable()->default('');
            $table->string('client_state', 35)->nullable()->default('');
            $table->string('client_zip', 15)->nullable()->default('');
            $table->string('client_country', 35)->nullable()->default('');
            $table->string('client_phone', 20)->nullable()->default('');
            $table->string('client_mobile', 20)->nullable()->default('');
            $table->string('client_email', 100)->nullable()->default('');
            $table->string('client_web', 100)->nullable()->default('');
            $table->integer('client_active')->default(1);

            $table->index('client_active', 'client_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_clients');
    }
};
