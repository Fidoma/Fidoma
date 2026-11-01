<?php

namespace Tests\Feature\Clients;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Features\Quotes\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Features\Reservations\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InlineClientCreationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('documentCreatePages')]
    public function test_complete_client_form_is_available_when_creating_documents(string $page): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test($page)
            ->assertFormComponentActionExists('client_id', 'createOption')
            ->callFormComponentAction('client_id', 'createOption', [
                'client_company' => 'Musterkunde GmbH',
                'client_active' => true,
                'client_address_1' => 'Musterstraße 12',
                'client_zip' => '12345',
                'client_city' => 'Musterstadt',
                'client_country' => 'DE',
                'client_email' => 'kunde@example.test',
                'client_phone' => '030 123456',
                'client_mobile' => '0170 123456',
                'client_web' => 'https://example.test',
                'client_vat_id' => 'DE123456789',
                'client_tax_code' => '12/345/67890',
                'client_einvoicing_active' => true,
                'client_einvoicing_version' => 'XRechnung 3.0 (CII)',
                'client_einvoicing_reference' => 'Leitweg-ID-123',
            ]);

        $client = Client::query()->where('client_email', 'kunde@example.test')->firstOrFail();

        $component->assertFormSet(['client_id' => $client->getKey()]);

        $this->assertSame('Musterkunde GmbH', $client->client_name);
        $this->assertSame('Musterstraße 12', $client->client_address_1);
        $this->assertSame('Leitweg-ID-123', $client->client_einvoicing_reference);
    }

    /** @return array<string, array{class-string}> */
    public static function documentCreatePages(): array
    {
        return [
            'invoice' => [CreateInvoice::class],
            'quote' => [CreateQuote::class],
            'reservation' => [CreateReservation::class],
        ];
    }
}
