<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeatureTranslationsTest extends TestCase
{
    /** @return array<string, array{string, string, string}> */
    public static function featureTranslations(): array
    {
        return [
            'bank transactions' => ['bank-transactions', 'Banktransaktionen', 'Bank transactions'],
            'clients' => ['clients', 'Kunden', 'Clients'],
            'documents' => ['documents', 'Dokumente', 'Documents'],
            'email templates' => ['email-templates', 'E-Mail-Vorlagen', 'Email templates'],
            'families' => ['families', 'Produktfamilien', 'Product families'],
            'invoice groups' => ['invoice-groups', 'Rechnungsgruppen', 'Invoice groups'],
            'invoices' => ['invoices', 'Rechnungen', 'Invoices'],
            'payment methods' => ['payment-methods', 'Zahlungsarten', 'Payment methods'],
            'payments' => ['payments', 'Zahlungen', 'Payments'],
            'products' => ['products', 'Produkte', 'Products'],
            'quotes' => ['quotes', 'Angebote', 'Quotes'],
            'reports' => ['reports', 'Berichte', 'Reports'],
            'reservations' => ['reservations', 'Reservierungen', 'Reservations'],
            'tax rates' => ['tax-rates', 'Steuersätze', 'Tax rates'],
            'units' => ['units', 'Einheiten', 'Units'],
            'users' => ['users', 'Benutzer', 'Users'],
        ];
    }

    #[DataProvider('featureTranslations')]
    public function test_feature_translations_are_loaded_from_their_feature_directory(
        string $namespace,
        string $german,
        string $english,
    ): void {
        $originalLocale = app()->getLocale();

        try {
            app()->setLocale('de');
            $this->assertSame($german, __($namespace.'::messages.feature'));

            app()->setLocale('en');
            $this->assertSame($english, __($namespace.'::messages.feature'));
        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
