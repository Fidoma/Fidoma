<?php

namespace Tests\Feature\Clients;

use App\Features\Clients\Actions\CreateClient;
use App\Features\Clients\Filament\Resources\Clients\Pages\CreateClient as CreateClientPage;
use App\Features\Clients\Filament\Resources\Clients\Pages\ListClients;
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
            'client_name' => 'Musterkunde',
            'client_active' => true,
        ]);

        $this->assertSame('KD-2026-0042', $client->client_number);
        $this->assertSame($group->getKey(), $client->invoice_group_id);
        $this->assertSame(43, $group->refresh()->invoice_group_next_id);
    }

    public function test_client_number_is_not_an_input_field(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateClientPage::class)
            ->assertFormFieldDoesNotExist('client_number')
            ->assertFormFieldDoesNotExist('invoice_group_id');
    }

    public function test_client_number_is_available_in_the_client_table(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ListClients::class)
            ->assertTableColumnExists('client_number')
            ->assertTableFilterExists('client_number');
    }
}
