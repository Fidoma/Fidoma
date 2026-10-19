<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_projects', function (Blueprint $table): void {
            $table->integer('invoice_group_id')->nullable()->after('client_id')->index();
            $table->string('project_number', 100)->nullable()->after('invoice_group_id')->unique();
            $table->string('project_status', 30)->default('draft')->after('project_name')->index();
            $table->longText('project_description')->nullable()->after('project_status');
            $table->date('project_start_date')->nullable()->after('project_description');
            $table->date('project_end_date')->nullable()->after('project_start_date');
            $table->decimal('contract_amount', 20, 2)->nullable()->after('project_end_date');
            $table->integer('quote_id')->nullable()->after('contract_amount')->index();
            $table->unsignedBigInteger('contract_id')->nullable()->after('quote_id')->index();
            $table->dateTime('created_at')->nullable()->after('contract_id');
            $table->dateTime('updated_at')->nullable()->after('created_at');
        });

        Schema::table('ip_invoices', function (Blueprint $table): void {
            $table->integer('project_id')->nullable()->after('client_id')->index();
            $table->string('billing_type', 30)->default('standard')->after('invoice_type')->index();
        });

        if (! DB::table('ip_invoice_groups')->where('invoice_group_name', 'Projekte')->exists()) {
            DB::table('ip_invoice_groups')->insert([
                'invoice_group_name' => 'Projekte',
                'invoice_group_identifier_format' => 'PR-{{{year}}}-{{{id}}}',
                'invoice_group_next_id' => 1,
                'invoice_group_left_pad' => 4,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ip_invoice_groups')
            ->where('invoice_group_name', 'Projekte')
            ->whereNotIn('invoice_group_id', DB::table('ip_projects')->whereNotNull('invoice_group_id')->select('invoice_group_id'))
            ->delete();

        Schema::table('ip_invoices', function (Blueprint $table): void {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['billing_type']);
            $table->dropColumn(['project_id', 'billing_type']);
        });

        Schema::table('ip_projects', function (Blueprint $table): void {
            $table->dropUnique(['project_number']);
            $table->dropIndex(['invoice_group_id']);
            $table->dropIndex(['project_status']);
            $table->dropIndex(['quote_id']);
            $table->dropIndex(['contract_id']);
            $table->dropColumn([
                'invoice_group_id',
                'project_number',
                'project_status',
                'project_description',
                'project_start_date',
                'project_end_date',
                'contract_amount',
                'quote_id',
                'contract_id',
                'created_at',
                'updated_at',
            ]);
        });
    }
};
