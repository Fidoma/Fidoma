<?php

namespace Tests\Feature\Features\Invoices;

use App\Features\Clients\Models\Client;
use App\Features\Invoices\Actions\CreateInvoice;
use App\Features\Invoices\Enums\InvoiceBillingType;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Filament\Resources\Invoices\Pages\CreateInvoice as CreateInvoicePage;
use App\Features\Projects\Enums\ProjectStatus;
use App\Features\Projects\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class ProjectInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        DB::table('ip_settings')->where('setting_key', 'module_enabled_projects')->delete();

        parent::tearDown();
    }

    public function test_progress_invoice_keeps_its_project_and_billing_type(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Bauherr GmbH',
            'client_active' => true,
        ]);
        $project = $this->createProject($client);

        $this->actingAs($user);

        $invoice = app(CreateInvoice::class)->handle([
            'client_id' => $client->getKey(),
            'project_id' => $project->getKey(),
            'billing_type' => InvoiceBillingType::Progress,
            'invoice_date_created' => '2026-10-19',
            'invoice_date_due' => '2026-11-18',
            'invoice_status_id' => InvoiceStatus::Draft,
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
        ]);

        $this->assertSame(InvoiceBillingType::Progress, $invoice->billing_type);
        $this->assertTrue($invoice->project->is($project));
    }

    public function test_progress_invoice_form_requires_a_project(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Bauherr GmbH',
            'client_active' => true,
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)
            ->test(CreateInvoicePage::class)
            ->set('data.billing_type', InvoiceBillingType::Progress->value)
            ->fillForm([
                'client_id' => $client->getKey(),
                'invoice_date_created' => '2026-10-19',
                'invoice_date_due' => '2026-11-18',
            ])
            ->call('create')
            ->assertHasFormErrors(['project_id' => 'required']);
    }

    public function test_project_select_follows_the_projects_module_while_billing_type_remains_available(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        DB::table('ip_settings')->insert([
            'setting_key' => 'module_enabled_projects',
            'setting_value' => '0',
        ]);

        Livewire::actingAs($user)
            ->test(CreateInvoicePage::class)
            ->assertFormFieldExists('project_id', fn ($field): bool => $field->isHidden())
            ->assertFormFieldExists('billing_type', fn ($field): bool => ! $field->isHidden());
    }

    public function test_disabled_projects_module_rejects_new_project_assignments_without_deleting_existing_data(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Bauherr GmbH',
            'client_active' => true,
        ]);
        $project = $this->createProject($client);
        $this->actingAs($user);

        $invoice = app(CreateInvoice::class)->handle([
            'client_id' => $client->getKey(),
            'project_id' => $project->getKey(),
            'invoice_date_created' => '2026-10-19',
            'invoice_date_due' => '2026-11-18',
        ]);

        DB::table('ip_settings')->insert([
            'setting_key' => 'module_enabled_projects',
            'setting_value' => '0',
        ]);

        $this->assertSame($project->getKey(), $invoice->refresh()->project_id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The projects module is not enabled');

        app(CreateInvoice::class)->handle([
            'client_id' => $client->getKey(),
            'project_id' => $project->getKey(),
            'invoice_date_created' => '2026-10-20',
            'invoice_date_due' => '2026-11-19',
        ]);
    }

    public function test_progress_and_final_invoices_remain_available_without_the_projects_module(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'client_name' => 'Bauherr GmbH',
            'client_active' => true,
        ]);
        $this->actingAs($user);

        DB::table('ip_settings')->insert([
            'setting_key' => 'module_enabled_projects',
            'setting_value' => '0',
        ]);

        $invoice = app(CreateInvoice::class)->handle([
            'client_id' => $client->getKey(),
            'billing_type' => InvoiceBillingType::Progress,
            'invoice_date_created' => '2026-10-19',
            'invoice_date_due' => '2026-11-18',
        ]);

        $this->assertSame(InvoiceBillingType::Progress, $invoice->billing_type);
        $this->assertNull($invoice->project_id);
    }

    private function createProject(Client $client): Project
    {
        return Project::query()->create([
            'client_id' => $client->getKey(),
            'invoice_group_id' => 1,
            'project_number' => 'PR-2026-'.str_pad((string) $client->getKey(), 4, '0', STR_PAD_LEFT),
            'project_name' => 'Neubau Musterstraße 12',
            'project_status' => ProjectStatus::Active,
        ]);
    }
}
