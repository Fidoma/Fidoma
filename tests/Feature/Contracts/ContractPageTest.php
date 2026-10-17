<?php

namespace Tests\Feature\Contracts;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\CreateContractTemplate;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\ListContractTemplates;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_page_contains_the_tiptap_rich_editor(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertStringEndsWith('/admin/contract-templates', ContractTemplateResource::getUrl());

        $this->actingAs($user)
            ->get(ContractTemplateResource::getUrl())
            ->assertOk()
            ->assertSeeText('Vertragsvorlage');

        Livewire::actingAs($user)
            ->test(ListContractTemplates::class)
            ->assertTableActionVisible('create');
    }

    public function test_template_and_contract_content_are_required(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateContractTemplate::class)
            ->assertFormFieldExists('name', fn ($field): bool => $field->isRequired())
            ->assertFormFieldExists('content', fn ($field): bool => $field->isRequired())
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'content',
            ]);
    }

    public function test_contract_template_can_be_created_from_the_editor(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateContractTemplate::class)
            ->fillForm([
                'name' => 'Mietvertrag Standard',
                'content' => '<p>{{text:client_name}} – {{text:issuer_name}} – {{date:contract_start_date}}</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('contract_templates', [
            'name' => 'Mietvertrag Standard',
            'content' => '<p>{{text:client_name}} – {{text:issuer_name}} – {{date:contract_start_date}}</p>',
            'status' => 'draft',
            'version' => 1,
        ]);
    }
}
