<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_template_fields', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('field_key', 100);
            $table->string('label');
            $table->string('field_type', 30);
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['template_id', 'field_key']);
            $table->foreign('template_id')
                ->references('id')
                ->on('contract_templates')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_template_fields');
    }
};
