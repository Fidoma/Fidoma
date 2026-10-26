<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tourism_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('reporting_year');
            $table->unsignedTinyInteger('reporting_month');
            $table->unsignedInteger('beds_offered');
            $table->unsignedInteger('available_guest_room_days')->nullable();
            $table->unsignedInteger('occupied_guest_room_days')->nullable();
            $table->unsignedTinyInteger('closure_day')->nullable();
            $table->date('reopening_date')->nullable();
            $table->unsignedTinyInteger('deregistration_day')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->string('data_source', 20)->default('manual');
            $table->timestamps();
            $table->unique(['reporting_year', 'reporting_month'], 'tourism_reports_period_unique');
        });

        Schema::create('tourism_report_country_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tourism_report_id')->constrained('tourism_reports')->cascadeOnDelete();
            $table->string('country_key', 50);
            $table->unsignedInteger('arrivals')->nullable();
            $table->unsignedInteger('overnight_stays')->nullable();
            $table->timestamps();
            $table->unique(['tourism_report_id', 'country_key'], 'tourism_report_country_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tourism_report_country_values');
        Schema::dropIfExists('tourism_reports');
    }
};
