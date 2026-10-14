<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('invoicing_contact', 50)->nullable()->after('company_name');
            $table->string('address_line_1')->nullable()->after('invoicing_contact');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('postal_code', 20)->nullable()->after('address_line_2');
            $table->string('city')->nullable()->after('postal_code');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('phone', 50)->nullable()->after('country');
            $table->string('fax', 50)->nullable()->after('phone');
            $table->string('mobile', 50)->nullable()->after('fax');
            $table->string('website')->nullable()->after('mobile');
            $table->string('vat_id', 50)->nullable()->after('website');
            $table->string('tax_number', 50)->nullable()->after('vat_id');
            $table->string('bank_name', 100)->nullable()->after('tax_number');
            $table->string('iban', 34)->nullable()->after('bank_name');
            $table->string('bic', 11)->nullable()->after('iban');
            $table->string('remittance_text', 105)->nullable()->after('bic');
            $table->string('gln', 13)->nullable()->after('remittance_text');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'invoicing_contact',
                'address_line_1',
                'address_line_2',
                'postal_code',
                'city',
                'state',
                'country',
                'phone',
                'fax',
                'mobile',
                'website',
                'vat_id',
                'tax_number',
                'bank_name',
                'iban',
                'bic',
                'remittance_text',
                'gln',
            ]);
        });
    }
};
