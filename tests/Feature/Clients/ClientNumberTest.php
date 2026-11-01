<?php

namespace Tests\Feature\Clients;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Clients\Filament\Resources\Clients\Pages\CreateClient as CreateClientPage;
use App\Features\Clients\Filament\Resources\Clients\Pages\EditClient as EditClientPage;
use App\Features\Clients\Filament\Resources\Clients\Pages\ListClients;
use App\Features\Clients\Models\Client;
use App\Features\Clients\Support\ClientCountryOptions;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_number_is_generated_from_the_client_number_group(): void
    {
        $group = InvoiceGroup::query()->where('invoice_group_name', 'Kunden')->firstOrFail();
        $group->update([
            'invoice_group_identifier_format' => 'KD-{{{year}}}-{{{id}}}',
            'invoice_group_next_id' => 42,
            'invoice_group_left_pad' => 4,
        ]);

        $client = app(CreateClient::class)->handle([
            'client_company' => 'Musterkunde GmbH',
            'client_active' => true,
        ]);

        $this->assertSame('KD-2026-0042', $client->client_number);
        $this->assertSame('Musterkunde GmbH', $client->client_name);
        $this->assertSame('Musterkunde GmbH', $client->client_company);
        $this->assertSame($group->getKey(), $client->invoice_group_id);
        $this->assertSame(43, $group->refresh()->invoice_group_next_id);
    }

    public function test_client_number_is_not_an_input_field(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateClientPage::class)
            ->assertFormSet(['client_einvoicing_active' => true])
            ->assertFormFieldExists('client_company')
            ->assertFormFieldExists('client_active')
            ->assertFormFieldExists('client_country')
            ->assertFormFieldDoesNotExist('client_title')
            ->assertFormFieldDoesNotExist('client_name')
            ->assertFormFieldDoesNotExist('client_surname')
            ->assertFormFieldDoesNotExist('client_state')
            ->assertFormFieldDoesNotExist('client_address_2')
            ->assertFormFieldDoesNotExist('client_invoicing_contact')
            ->assertFormFieldDoesNotExist('client_fax')
            ->assertFormFieldDoesNotExist('client_number')
            ->assertFormFieldDoesNotExist('invoice_group_id');
    }

    public function test_country_options_are_localized_and_keep_existing_free_text_values(): void
    {
        $options = ClientCountryOptions::all('de');

        $this->assertGreaterThan(240, count($options));
        $this->assertSame('Deutschland', $options['DE']);
        $this->assertSame('Vereinigte Staaten', $options['US']);
        $this->assertArrayNotHasKey('ZZ', $options);
        $this->assertSame('Deutschland', ClientCountryOptions::withCurrent('Deutschland', 'de')['Deutschland']);
    }

    public function test_client_number_is_available_in_the_client_table(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ListClients::class)
            ->assertTableColumnExists('client_number')
            ->assertTableFilterExists('client_number');
    }

    public function test_existing_name_is_used_as_company_and_stays_synchronized_when_edited(): void
    {
        $client = Client::query()->create([
            'client_name' => 'Bestehender Kunde',
            'client_active' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditClientPage::class, ['record' => $client->getRouteKey()])
            ->assertFormSet(['client_company' => 'Bestehender Kunde'])
            ->fillForm(['client_company' => 'Neue Bezeichnung GmbH'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ip_clients', [
            'client_id' => $client->getKey(),
            'client_name' => 'Neue Bezeichnung GmbH',
            'client_company' => 'Neue Bezeichnung GmbH',
        ]);
    }
}
