<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tourism_reports', 'establishment_id')) {
            Schema::table('tourism_reports', function (Blueprint $table): void {
                $table->dropForeign(['establishment_id']);
            });

            Schema::table('tourism_reports', function (Blueprint $table): void {
                $table->dropUnique('tourism_reports_period_unique');
                $table->dropColumn('establishment_id');
                $table->unique(['reporting_year', 'reporting_month'], 'tourism_reports_period_unique');
            });
        }

        Schema::dropIfExists('tourism_establishments');
    }

    public function down(): void
    {
        if (! Schema::hasTable('tourism_establishments')) {
            Schema::create('tourism_establishments', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('tourism_reports', 'establishment_id')) {
            Schema::table('tourism_reports', function (Blueprint $table): void {
                $table->dropUnique('tourism_reports_period_unique');
                $table->foreignId('establishment_id')
                    ->nullable()
                    ->constrained('tourism_establishments')
                    ->cascadeOnDelete();
                $table->unique(
                    ['establishment_id', 'reporting_year', 'reporting_month'],
                    'tourism_reports_period_unique',
                );
            });
        }
    }
};
