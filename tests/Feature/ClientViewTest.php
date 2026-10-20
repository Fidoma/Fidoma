<?php

namespace Tests\Feature;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_index_uses_an_options_menu_and_row_link_for_details(): void
    {
        $user = User::factory()->create();
        Client::query()->create([
            'client_name' => 'Max Mustermann',
            'client_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/clients')
            ->assertOk()
            ->assertSeeText('Bearbeiten')
            ->assertSeeText('Rechnung erstellen')
            ->assertSeeText('Angebot erstellen')
            ->assertSeeText('Archivieren')
            ->assertDontSeeText('Löschen')
            ->assertDontSeeText('Kunde anzeigen');
    }

    public function test_client_view_contains_detail_and_document_tabs(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Max Mustermann',
            'client_company' => 'Muster GmbH',
            'client_active' => true,
        ]);

        $this->actingAs($user)
            ->get("/admin/clients/{$client->getKey()}")
            ->assertOk()
            ->assertSeeText('Details')
            ->assertSeeText('Angebote')
            ->assertSeeText('Rechnungen')
            ->assertSeeText('Zahlungen')
            ->assertSeeText('Kontaktinformationen')
            ->assertSeeText('Rechnungsinformationen');
    }

    public function test_client_edit_form_is_split_into_business_sections(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Max Mustermann',
            'client_active' => true,
        ]);

        $this->actingAs($user)
            ->get("/admin/clients/{$client->getKey()}/edit")
            ->assertOk()
            ->assertSeeText('Stammdaten')
            ->assertSeeText('Anschrift')
            ->assertSeeText('Kontakt')
            ->assertSeeText('Rechnungs- und Steuerdaten');
    }

    public function test_invoice_created_from_client_details_has_the_client_preselected(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Max Mustermann',
            'client_active' => true,
        ]);

        Livewire::withQueryParams(['client_id' => $client->getKey()])
            ->actingAs($user)
            ->test(CreateInvoice::class)
            ->assertFormSet(['client_id' => $client->getKey()]);
    }
}
