<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ip_clients ENGINE=InnoDB');

        Schema::create('contracts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->integer('client_id');
            $table->unsignedBigInteger('issuer_id');
            $table->string('number', 100)->unique();
            $table->date('contract_create_date');
            $table->date('contract_start_date');
            $table->date('contract_end_date');
            $table->longText('rendered_content')->nullable();
            $table->string('status', 30)->default('draft');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('template_id');
            $table->index('client_id');
            $table->index('issuer_id');
            $table->index('status');

            $table->foreign('template_id')
                ->references('id')
                ->on('contract_templates')
                ->restrictOnDelete();
            $table->foreign('client_id')
                ->references('client_id')
                ->on('ip_clients')
                ->restrictOnDelete();
            $table->foreign('issuer_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');

        DB::statement('ALTER TABLE ip_clients ENGINE=MyISAM');
    }
};
