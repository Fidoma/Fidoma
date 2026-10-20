<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terminals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(20007);
            $table->string('protocol')->default('zvt');
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('connection_status')->default('unknown');
            $table->string('operating_status')->default('idle');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_enabled']);
            $table->index('payment_method_id');
        });

        Schema::create('terminal_payment_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_terminal_id')->constrained('payment_terminals')->restrictOnDelete();
            $table->unsignedInteger('invoice_id');
            $table->unsignedInteger('payment_id')->nullable();
            $table->decimal('amount', 20, 2);
            $table->string('status');
            $table->string('gateway_mode');
            $table->string('gateway_reference')->nullable();
            $table->text('status_message')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // The legacy InvoicePlane tables use MyISAM in existing installations,
            // so invoice, payment, and payment-method references are indexed rather
            // than constrained at the database level. Service transactions enforce
            // their integrity while retaining compatibility with those installations.
            $table->index('payment_id');
            $table->index(['payment_terminal_id', 'status']);
            $table->index(['invoice_id', 'status']);
            $table->unique('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_payment_attempts');
        Schema::dropIfExists('payment_terminals');
    }
};
