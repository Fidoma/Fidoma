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
use Livewire\Livewire;
use Tests\TestCase;

class ProjectInvoiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_invoice_items_keep_the_table_layout_on_mobile(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs(User::factory()->create())
            ->test(CreateInvoicePage::class)
            ->assertSee('invoice-items-table');
    }

    private function createProject(Client $client): Project
    {
        return Project::query()->create([
            'client_id' => $client->getKey(),
            'invoice_group_id' => 1,
            'project_number' => 'PR-2026-0001',
            'project_name' => 'Neubau Musterstraße 12',
            'project_status' => ProjectStatus::Active,
        ]);
    }
}
