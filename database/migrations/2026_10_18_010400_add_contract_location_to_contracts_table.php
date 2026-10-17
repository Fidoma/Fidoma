<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->string('contract_location')->after('contract_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn('contract_location');
        });
    }
};
