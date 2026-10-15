<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoice_layouts')
            ->where('html', 'not like', '%{{ tax_rows }}%')
            ->orderBy('id')
            ->each(function (object $layout): void {
                $html = str_replace(
                    '<tr><td>Steuer</td><td>{{ totals.tax }}</td></tr>',
                    "{{ tax_rows }}\n            <tr><td>Steuer gesamt</td><td>{{ totals.tax }}</td></tr>",
                    $layout->html,
                );

                DB::table('invoice_layouts')->where('id', $layout->id)->update(['html' => $html]);
            });
    }

    public function down(): void
    {
        DB::table('invoice_layouts')
            ->orderBy('id')
            ->each(function (object $layout): void {
                $html = str_replace(
                    "{{ tax_rows }}\n            <tr><td>Steuer gesamt</td><td>{{ totals.tax }}</td></tr>",
                    '<tr><td>Steuer</td><td>{{ totals.tax }}</td></tr>',
                    $layout->html,
                );

                DB::table('invoice_layouts')->where('id', $layout->id)->update(['html' => $html]);
            });
    }
};
