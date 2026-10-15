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
                $html = preg_replace(
                    '/(<tr\b[^>]*>(?:(?!<\/tr>)[\s\S])*?\{\{\s*totals\.tax\s*\}\}(?:(?!<\/tr>)[\s\S])*?<\/tr>)/i',
                    "{{ tax_rows }}\n        $1",
                    $layout->html,
                    1,
                );

                if (is_string($html) && $html !== $layout->html) {
                    DB::table('invoice_layouts')->where('id', $layout->id)->update(['html' => $html]);
                }
            });
    }

    public function down(): void
    {
        DB::table('invoice_layouts')
            ->orderBy('id')
            ->each(function (object $layout): void {
                $html = preg_replace('/\{\{\s*tax_rows\s*\}\}\s*/i', '', $layout->html, 1);

                DB::table('invoice_layouts')->where('id', $layout->id)->update(['html' => $html]);
            });
    }
};
