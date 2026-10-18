<?php

namespace Tests\Feature\Contracts;

use App\Features\Clients\Models\Client;
use App\Features\Contracts\Filament\Resources\Contracts\ContractResource;
use App\Features\Contracts\Filament\Resources\Contracts\Pages\CreateContract;
use App\Features\Contracts\Filament\Resources\Contracts\Pages\ListContracts;
use App\Features\Contracts\Models\Contract;
use App\Features\Contracts\Models\ContractTemplate;
use App\Features\Contracts\Models\ContractTemplateField;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_page_shows_client_and_template_selection(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertStringEndsWith('/admin/contracts', ContractResource::getUrl());

        $this->actingAs($user)
            ->get(ContractResource::getUrl())
            ->assertOk()
            ->assertSeeText('Vertrag');

        Livewire::actingAs($user)
            ->test(ListContracts::class)
            ->assertTableActionVisible('create');
    }

    public function test_selecting_a_template_builds_the_contract_form_and_custom_fields(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Testkunde',
            'client_active' => true,
        ]);
        $template = ContractTemplate::query()->create([
            'name' => 'Mietvertrag',
            'content' => '<p>{{custom.monthly_rent}}</p>',
        ]);
        ContractTemplateField::query()->create([
            'template_id' => $template->getKey(),
            'field_key' => 'monthly_rent',
            'label' => 'Monatsmiete',
            'field_type' => 'currency',
            'is_required' => true,
            'sort_order' => 1,
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateContract::class)
            ->assertFormFieldExists('client_id')
            ->assertFormFieldExists('template_id')
            ->set('data.client_id', $client->getKey())
            ->set('data.template_id', $template->getKey())
            ->assertFormFieldExists('number')
            ->assertFormFieldDoesNotExist('invoice_group_id')
            ->assertFormFieldExists('contract_location')
            ->assertFormFieldExists('contract_create_date')
            ->assertFormFieldExists('contract_start_date')
            ->assertFormFieldExists('contract_end_date', fn ($field): bool => ! $field->isRequired())
            ->assertFormFieldExists(
                'custom_fields.monthly_rent',
                fn ($field): bool => $field->isRequired(),
            )
            ->assertSeeText('Monatsmiete');
    }

    public function test_a_contract_and_its_custom_values_can_be_created(): void
    {
        $this->deleteContractInvoiceGroups();

        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Vertragskunde',
            'client_active' => true,
        ]);
        $template = ContractTemplate::query()->create([
            'name' => 'Servicevertrag',
            'content' => '<p>{{custom.service_level}}</p>',
        ]);
        $field = ContractTemplateField::query()->create([
            'template_id' => $template->getKey(),
            'field_key' => 'service_level',
            'label' => 'Service-Level',
            'field_type' => 'text',
            'is_required' => true,
        ]);
        $invoiceGroup = InvoiceGroup::query()->create([
            'invoice_group_name' => 'Verträge',
            'invoice_group_identifier_format' => 'V-{{{id}}}',
            'invoice_group_next_id' => 42,
            'invoice_group_left_pad' => 4,
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateContract::class)
            ->set('data.template_id', $template->getKey())
            ->fillForm([
                'client_id' => $client->getKey(),
                'template_id' => $template->getKey(),
                'contract_location' => 'Berlin',
                'contract_create_date' => '2026-10-18',
                'contract_start_date' => '2026-11-01',
                'contract_end_date' => null,
                'custom_fields' => ['service_level' => 'Premium'],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'template_id' => $template->getKey(),
            'client_id' => $client->getKey(),
            'issuer_id' => $user->getKey(),
            'invoice_group_id' => $invoiceGroup->getKey(),
            'number' => 'V-0042',
            'contract_location' => 'Berlin',
            'contract_end_date' => null,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('contract_field_values', [
            'template_field_id' => $field->getKey(),
            'value' => 'Premium',
        ]);

        $contract = Contract::query()->where('number', 'V-0042')->firstOrFail();

        $this->actingAs($user)
            ->get(ContractResource::getUrl('view', ['record' => $contract]))
            ->assertOk()
            ->assertSeeText('Vertrag V-0042')
            ->assertSeeText('Vertragskunde')
            ->assertSeeText('Service-Level')
            ->assertSeeText('Premium');
    }

    public function test_a_manually_entered_contract_number_is_preserved(): void
    {
        $this->deleteContractInvoiceGroups();

        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Vertragskunde',
            'client_active' => true,
        ]);
        $template = ContractTemplate::query()->create([
            'name' => 'Servicevertrag',
            'content' => '<p>Vertrag</p>',
        ]);
        $invoiceGroup = InvoiceGroup::query()->create([
            'invoice_group_name' => 'Vertrag',
            'invoice_group_identifier_format' => 'V-{{{id}}}',
            'invoice_group_next_id' => 7,
            'invoice_group_left_pad' => 4,
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateContract::class)
            ->set('data.template_id', $template->getKey())
            ->fillForm([
                'client_id' => $client->getKey(),
                'template_id' => $template->getKey(),
                'number' => 'V-MANUELL-7',
                'contract_location' => 'Berlin',
                'contract_create_date' => '2026-10-18',
                'contract_start_date' => '2026-11-01',
                'contract_end_date' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'invoice_group_id' => $invoiceGroup->getKey(),
            'number' => 'V-MANUELL-7',
        ]);
        $this->assertDatabaseHas('ip_invoice_groups', [
            'invoice_group_id' => $invoiceGroup->getKey(),
            'invoice_group_next_id' => 7,
        ]);
    }

    private function deleteContractInvoiceGroups(): void
    {
        InvoiceGroup::query()
            ->where(function ($query): void {
                $query
                    ->where('invoice_group_name', 'like', '%Vertrag%')
                    ->orWhere('invoice_group_name', 'like', '%Verträg%');
            })
            ->delete();
    }
}
