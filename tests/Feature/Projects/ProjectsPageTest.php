<?php

namespace Tests\Feature\Projects;

use App\Features\Clients\Models\Client;
use App\Features\InvoiceGroups\Models\InvoiceGroup;
use App\Features\Projects\Enums\ProjectStatus;
use App\Features\Projects\Filament\Resources\Projects\Pages\CreateProject;
use App\Features\Projects\Filament\Resources\Projects\Pages\ListProjects;
use App\Features\Projects\Filament\Resources\Projects\ProjectResource;
use App\Features\Projects\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_resource_is_available_in_the_admin_panel(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertStringEndsWith('/admin/projects', ProjectResource::getUrl());

        $this->actingAs($user)
            ->get(ProjectResource::getUrl())
            ->assertOk()
            ->assertSeeText('Projekte');

        $table = Livewire::actingAs($user)
            ->test(ListProjects::class)
            ->instance()
            ->getTable();

        $this->assertTrue($table->persistsFiltersInSession());
        $this->assertTrue($table->persistsColumnsInSession());
        $this->assertTrue($table->persistsSearchInSession());
        $this->assertTrue($table->persistsSortInSession());
        $this->assertTrue($table->hasColumnManager());
        $this->assertTrue(collect($table->getColumns())->every(fn ($column): bool => $column->isToggleable()));
    }

    public function test_project_uses_the_configured_number_group(): void
    {
        InvoiceGroup::query()->where('invoice_group_name', 'like', '%Projekt%')->delete();
        $group = InvoiceGroup::query()->create([
            'invoice_group_name' => 'Projekte',
            'invoice_group_identifier_format' => 'PR-{{{year}}}-{{{id}}}',
            'invoice_group_next_id' => 42,
            'invoice_group_left_pad' => 4,
        ]);
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Bauherr GmbH',
            'client_active' => true,
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->assertFormFieldDoesNotExist('invoice_group_id')
            ->fillForm([
                'client_id' => $client->getKey(),
                'project_name' => 'Projekt Elbchaussee 42',
                'project_status' => ProjectStatus::Active->value,
                'project_start_date' => '2026-11-01',
                'contract_amount' => 100000,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $project = Project::query()
            ->where('project_name', 'Projekt Elbchaussee 42')
            ->firstOrFail();

        $this->assertSame('PR-2026-0042', $project->project_number);
        $this->assertSame(ProjectStatus::Active, $project->project_status);
        $this->assertSame('100000.00', $project->contract_amount);
        $this->assertSame(43, $group->refresh()->invoice_group_next_id);
    }

    public function test_project_client_select_uses_the_client_name(): void
    {
        $company = Client::query()->create([
            'client_name' => 'Bauherr GmbH',
            'client_active' => true,
        ]);
        $person = Client::query()->create([
            'client_name' => 'Max Mustermann',
            'client_active' => true,
        ]);
        $inactive = Client::query()->create([
            'client_name' => 'Inaktiver Kunde',
            'client_active' => false,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CreateProject::class)
            ->assertFormFieldExists('client_id', function (Select $field) use ($company, $person, $inactive): bool {
                $options = $field->getOptions();

                return $options[$company->getKey()] === 'Bauherr GmbH'
                    && $options[$person->getKey()] === 'Max Mustermann'
                    && ! array_key_exists($inactive->getKey(), $options);
            });
    }

    public function test_project_can_be_created_without_a_name(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Projektkunde',
            'client_active' => true,
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->assertFormFieldExists('project_name', fn ($field): bool => ! $field->isRequired())
            ->fillForm([
                'client_id' => $client->getKey(),
                'project_name' => null,
                'project_status' => ProjectStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'client_id' => $client->getKey(),
            'project_name' => null,
        ]);
    }
}
