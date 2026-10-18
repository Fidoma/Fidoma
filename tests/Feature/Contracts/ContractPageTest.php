<?php

namespace Tests\Feature\Contracts;

use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\CreateContractTemplate;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\EditContractTemplate;
use App\Features\Contracts\Filament\Resources\ContractTemplates\Pages\ListContractTemplates;
use App\Features\Contracts\Models\ContractTemplate;
use App\Features\Contracts\Models\ContractTemplateField;
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

    public function test_custom_placeholder_can_be_added_while_creating_a_template(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateContractTemplate::class)
            ->assertFormComponentActionExists('contract-template-placeholders', 'addPlaceholder')
            ->callFormComponentAction('contract-template-placeholders', 'addPlaceholder', [
                'label' => 'Monatliche Miete',
                'field_type' => 'date',
            ])
            ->assertSet('data.custom_placeholders', [[
                'field_key' => 'monatliche_miete',
                'label' => 'Monatliche Miete',
                'field_type' => 'date',
                'can_delete' => true,
            ]])
            ->fillForm([
                'name' => 'Mietvertrag mit Platzhalter',
                'content' => '<p>{{date:monatliche_miete}}</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $template = ContractTemplate::query()->where('name', 'Mietvertrag mit Platzhalter')->firstOrFail();

        $this->assertDatabaseHas('contract_template_fields', [
            'template_id' => $template->getKey(),
            'field_key' => 'monatliche_miete',
            'label' => 'Monatliche Miete',
            'field_type' => 'date',
        ]);
    }

    public function test_custom_placeholder_can_be_added_while_editing_a_template(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $template = ContractTemplate::query()->create([
            'name' => 'Servicevertrag',
            'content' => '<p>Inhalt</p>',
            'status' => 'draft',
            'version' => 1,
        ]);
        ContractTemplateField::query()->create([
            'template_id' => $template->getKey(),
            'field_key' => 'service_level',
            'label' => 'Service-Level',
            'field_type' => 'text',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(EditContractTemplate::class, ['record' => $template->getRouteKey()])
            ->assertSet('data.custom_placeholders', [[
                'field_key' => 'service_level',
                'label' => 'Service-Level',
                'field_type' => 'text',
                'can_delete' => true,
            ]])
            ->callFormComponentAction('contract-template-placeholders', 'addPlaceholder', [
                'label' => 'Ansprechpartner',
                'field_type' => 'text',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contract_template_fields', [
            'template_id' => $template->getKey(),
            'field_key' => 'ansprechpartner',
            'label' => 'Ansprechpartner',
            'field_type' => 'text',
        ]);
    }

    public function test_custom_placeholder_can_be_removed_while_editing_a_template(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $template = ContractTemplate::query()->create([
            'name' => 'Vorlage mit altem Feld',
            'content' => '<p>Inhalt</p>',
            'status' => 'draft',
            'version' => 1,
        ]);
        ContractTemplateField::query()->create([
            'template_id' => $template->getKey(),
            'field_key' => 'altes_feld',
            'label' => 'Altes Feld',
            'field_type' => 'text',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(EditContractTemplate::class, ['record' => $template->getRouteKey()])
            ->assertSee('Platzhalter löschen')
            ->set('data.custom_placeholders', [])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('contract_template_fields', [
            'template_id' => $template->getKey(),
            'field_key' => 'altes_feld',
        ]);
    }
}
