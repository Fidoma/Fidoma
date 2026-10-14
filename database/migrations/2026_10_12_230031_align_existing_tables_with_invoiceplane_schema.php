<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ip_clients', function (Blueprint $table) {
            $table->string('client_company', 150)->nullable()->after('client_name');
            $table->text('client_address_2')->nullable()->after('client_address_1');
            $table->text('client_fax')->nullable()->after('client_phone');
            $table->text('client_vat_id')->nullable()->after('client_web');
            $table->text('client_tax_code')->nullable()->after('client_vat_id');
            $table->string('client_language')->nullable()->default('system')->after('client_tax_code');
            $table->string('client_surname')->nullable()->after('client_active');
            $table->string('client_invoicing_contact', 50)->nullable()->after('client_surname');
            $table->string('client_title', 50)->nullable()->after('client_invoicing_contact');
            $table->boolean('client_einvoicing_active')->default(false)->after('client_title');
            $table->string('client_einvoicing_version', 25)->nullable()->after('client_einvoicing_active');
            $table->string('client_avs', 16)->nullable()->after('client_einvoicing_version');
            $table->string('client_insurednumber', 30)->nullable()->after('client_avs');
            $table->string('client_veka', 30)->nullable()->after('client_insurednumber');
            $table->date('client_birthdate')->nullable()->after('client_veka');
            $table->integer('client_gender')->nullable()->default(0)->after('client_birthdate');
        });

        if (Schema::hasColumn('ip_products', 'product_number') && ! Schema::hasColumn('ip_products', 'product_sku')) {
            Schema::table('ip_products', fn (Blueprint $table) => $table->renameColumn('product_number', 'product_sku'));
        }

        Schema::table('ip_products', function (Blueprint $table) {
            $table->text('provider_name')->nullable()->after('purchase_price');
            $table->integer('unit_id')->nullable()->after('tax_rate_id');
            $table->integer('product_tariff')->nullable()->after('unit_id');
        });

        Schema::table('ip_quotes', function (Blueprint $table) {
            $table->decimal('quote_discount_amount', 20, 2)->nullable()->after('quote_number');
            $table->decimal('quote_discount_percent', 20, 2)->nullable()->after('quote_discount_amount');
            $table->longText('notes')->nullable()->after('quote_url_key');
        });

        Schema::table('ip_quote_items', function (Blueprint $table) {
            $table->boolean('item_tax_included')->default(false)->after('item_tax_rate_id');
            $table->integer('item_product_id')->nullable()->after('item_tax_included');
            $table->decimal('item_discount_amount', 20, 2)->nullable()->after('item_price');
            $table->string('item_product_unit', 50)->nullable()->after('item_order');
            $table->integer('item_product_unit_id')->nullable()->after('item_product_unit');
        });

        Schema::table('ip_quote_item_amounts', function (Blueprint $table) {
            $table->decimal('item_discount', 20, 2)->nullable()->after('item_tax_total');
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            ALTER TABLE ip_clients
                MODIFY client_name TEXT NULL,
                MODIFY client_address_1 TEXT NULL,
                MODIFY client_city TEXT NULL,
                MODIFY client_state TEXT NULL,
                MODIFY client_zip TEXT NULL,
                MODIFY client_country TEXT NULL,
                MODIFY client_phone TEXT NULL,
                MODIFY client_mobile TEXT NULL,
                MODIFY client_email TEXT NULL,
                MODIFY client_web TEXT NULL
        SQL);

        DB::unprepared(<<<'SQL'
            ALTER TABLE ip_products
                MODIFY family_id INT NULL,
                MODIFY product_sku TEXT NULL,
                MODIFY product_name TEXT NULL,
                MODIFY product_price DECIMAL(20, 2) NULL,
                MODIFY purchase_price DECIMAL(20, 2) NULL,
                MODIFY tax_rate_id INT NULL
        SQL);

        DB::unprepared('ALTER TABLE ip_quotes MODIFY quote_number VARCHAR(100) NULL');

        DB::unprepared(<<<'SQL'
            ALTER TABLE ip_quote_items
                MODIFY item_name TEXT NULL,
                MODIFY item_description TEXT NULL,
                MODIFY item_quantity DECIMAL(20, 8) NULL,
                MODIFY item_price DECIMAL(20, 2) NULL
        SQL);

        DB::unprepared(<<<'SQL'
            ALTER TABLE ip_quote_item_amounts
                MODIFY item_subtotal DECIMAL(20, 2) NULL,
                MODIFY item_tax_total DECIMAL(20, 2) NULL,
                MODIFY item_total DECIMAL(20, 2) NULL
        SQL);

        DB::unprepared('ALTER TABLE ip_quote_tax_rates MODIFY quote_tax_rate_amount DECIMAL(20, 2) NULL');
        DB::unprepared('ALTER TABLE ip_tax_rates MODIFY tax_rate_name TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_quote_item_amounts', fn (Blueprint $table) => $table->dropColumn('item_discount'));

        Schema::table('ip_quote_items', function (Blueprint $table) {
            $table->dropColumn([
                'item_tax_included',
                'item_product_id',
                'item_discount_amount',
                'item_product_unit',
                'item_product_unit_id',
            ]);
        });

        Schema::table('ip_quotes', fn (Blueprint $table) => $table->dropColumn([
            'quote_discount_amount',
            'quote_discount_percent',
            'notes',
        ]));

        Schema::table('ip_products', fn (Blueprint $table) => $table->dropColumn([
            'provider_name',
            'unit_id',
            'product_tariff',
        ]));

        if (Schema::hasColumn('ip_products', 'product_sku') && ! Schema::hasColumn('ip_products', 'product_number')) {
            Schema::table('ip_products', fn (Blueprint $table) => $table->renameColumn('product_sku', 'product_number'));
        }

        Schema::table('ip_clients', fn (Blueprint $table) => $table->dropColumn([
            'client_company',
            'client_address_2',
            'client_fax',
            'client_vat_id',
            'client_tax_code',
            'client_language',
            'client_surname',
            'client_invoicing_contact',
            'client_title',
            'client_einvoicing_active',
            'client_einvoicing_version',
            'client_avs',
            'client_insurednumber',
            'client_veka',
            'client_birthdate',
            'client_gender',
        ]));
    }
};
