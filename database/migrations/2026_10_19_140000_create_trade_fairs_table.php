<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_fairs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('identity_key', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->text('website')->nullable();
            $table->boolean('is_local')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('source')->default('messe-stuttgart-csv');
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index(['is_active', 'is_local', 'start_date', 'end_date'], 'trade_fairs_scheduler_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_fairs');
    }
};
