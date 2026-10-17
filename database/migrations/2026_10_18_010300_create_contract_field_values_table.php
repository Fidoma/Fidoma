<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_field_values', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('template_field_id');
            $table->text('value')->nullable();

            $table->primary(['contract_id', 'template_field_id']);
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->cascadeOnDelete();
            $table->foreign('template_field_id')
                ->references('id')
                ->on('contract_template_fields')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_field_values');
    }
};
