<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_templates', function (Blueprint $table): void {
            $table->string('action', 100)->nullable()->after('area');
            $table->index(['area', 'action', 'is_active']);
        });

        DB::table('ai_templates')
            ->whereRaw('LOWER(title) LIKE ?', ['%reservierung%'])
            ->update(['action' => 'reservation.create']);

        DB::table('ai_templates')
            ->where(function ($query): void {
                $query
                    ->whereRaw('LOWER(title) LIKE ?', ['%kunde%'])
                    ->orWhereRaw('LOWER(title) LIKE ?', ['%client%']);
            })
            ->update(['action' => 'client.create']);
    }

    public function down(): void
    {
        Schema::table('ai_templates', function (Blueprint $table): void {
            $table->dropIndex(['area', 'action', 'is_active']);
            $table->dropColumn('action');
        });
    }
};
