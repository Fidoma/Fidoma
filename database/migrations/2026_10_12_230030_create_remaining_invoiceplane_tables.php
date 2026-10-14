<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that are managed by earlier, feature-specific migrations.
     *
     * @var list<string>
     */
    private const EXISTING_TABLES = [
        'ip_clients',
        'ip_families',
        'ip_products',
        'ip_quote_item_amounts',
        'ip_quote_items',
        'ip_quote_tax_rates',
        'ip_quotes',
        'ip_tax_rates',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->tableStatements() as $table => $statement) {
                if (in_array($table, self::EXISTING_TABLES, true)) {
                    continue;
                }

                DB::unprepared($statement);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse(array_keys($this->tableStatements())) as $table) {
            if (in_array($table, self::EXISTING_TABLES, true)) {
                continue;
            }

            Schema::dropIfExists($table);
        }
    }

    /**
     * @return array<string, string>
     */
    private function tableStatements(): array
    {
        $schema = file_get_contents(database_path('schema/invoiceplane-tables.sql'));

        if ($schema === false) {
            throw new RuntimeException('The InvoicePlane database schema could not be read.');
        }

        preg_match_all(
            '/CREATE TABLE IF NOT EXISTS `([^`]+)`.*?;(?=\s*(?:CREATE TABLE IF NOT EXISTS|\z))/s',
            $schema,
            $matches,
            PREG_SET_ORDER,
        );

        $statements = [];

        foreach ($matches as $match) {
            $statements[$match[1]] = $match[0];
        }

        return $statements;
    }
};
