<?php

use App\Features\InvoiceGroups\Services\InvoiceGroupNumberFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_clients', function (Blueprint $table): void {
            $table->integer('invoice_group_id')->nullable()->after('client_id')->index();
            $table->string('client_number', 100)->nullable()->after('invoice_group_id')->unique();
        });

        $invoiceGroupId = DB::table('ip_invoice_groups')
            ->where('invoice_group_name', 'Kunden')
            ->value('invoice_group_id');

        if (! $invoiceGroupId) {
            $invoiceGroupId = DB::table('ip_invoice_groups')->insertGetId([
                'invoice_group_name' => 'Kunden',
                'invoice_group_identifier_format' => 'KD-{{{id}}}',
                'invoice_group_next_id' => 1,
                'invoice_group_left_pad' => 5,
            ]);
        }

        $invoiceGroup = DB::table('ip_invoice_groups')->where('invoice_group_id', $invoiceGroupId)->first();
        $nextId = (int) $invoiceGroup->invoice_group_next_id;
        $formatter = app(InvoiceGroupNumberFormatter::class);

        DB::table('ip_clients')
            ->whereNull('client_number')
            ->orderBy('client_id')
            ->eachById(function (object $client) use ($formatter, $invoiceGroup, $invoiceGroupId, &$nextId): void {
                DB::table('ip_clients')->where('client_id', $client->client_id)->update([
                    'invoice_group_id' => $invoiceGroupId,
                    'client_number' => $formatter->format(
                        $invoiceGroup->invoice_group_identifier_format,
                        $nextId++,
                        $invoiceGroup->invoice_group_left_pad,
                    ),
                ]);
            }, column: 'client_id');

        DB::table('ip_invoice_groups')->where('invoice_group_id', $invoiceGroupId)->update([
            'invoice_group_next_id' => $nextId,
        ]);
    }

    public function down(): void
    {
        Schema::table('ip_clients', function (Blueprint $table): void {
            $table->dropUnique(['client_number']);
            $table->dropIndex(['invoice_group_id']);
            $table->dropColumn(['invoice_group_id', 'client_number']);
        });

        DB::table('ip_invoice_groups')->where('invoice_group_name', 'Kunden')->delete();
    }
};
