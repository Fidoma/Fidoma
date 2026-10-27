<?php

namespace Tests\Feature\Clients;

use App\Features\Clients\Filament\Resources\Clients\Pages\ListClients;
use App\Features\Clients\Filament\Widgets\ClientDashboard;
use App\Features\Clients\Models\Client;
use App\Features\Clients\Services\ClientAnalytics;
use App\Features\Clients\Support\ClientAnalyticsPeriod;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-26 12:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_analytics_use_issued_unarchived_amounts_and_current_balances(): void
    {
        $user = User::factory()->create();
        $alpha = $this->client('Alpha GmbH', true, '2026-10-02');
        $beta = $this->client('Beta AG', true, '2026-01-10');
        $inactive = $this->client('Archiv GmbH', false, '2026-10-03');
        $noRevenue = $this->client('Ohne Umsatz GmbH', true, '2025-01-01');

        $this->invoice($user, $alpha, InvoiceType::Invoice, InvoiceStatus::Open, 100, 40);
        $this->invoice($user, $alpha, InvoiceType::Credit, InvoiceStatus::Open, -20, -20);
        $this->invoice($user, $beta, InvoiceType::Invoice, InvoiceStatus::Open, 200, 200);
        $this->invoice($user, $beta, InvoiceType::Invoice, InvoiceStatus::Draft, 900, 900);
        $this->invoice($user, $inactive, InvoiceType::Invoice, InvoiceStatus::Open, 500, 500, archived: true);

        $overview = app(ClientAnalytics::class)->overview(ClientAnalyticsPeriod::custom('2026-10-01', '2026-10-31'));

        $this->assertSame(4, $overview['total_clients']);
        $this->assertSame(3, $overview['active_clients']);
        $this->assertSame(2, $overview['new_clients']);
        $this->assertSame(280.0, $overview['revenue']);
        $this->assertSame(240.0, $overview['receivables']);
        $this->assertSame(140.0, $overview['average_revenue']);
        $this->assertSame(1, $overview['without_revenue_90_days']);
        $this->assertSame('Beta AG', $overview['rankings']['revenue'][0]['name']);
        $this->assertSame(200.0, $overview['rankings']['receivables'][0]['value']);
        $this->assertSame(1, $overview['rankings']['invoice_count'][0]['value']);
    }

    public function test_widget_switches_period_and_ranking_and_validates_custom_dates(): void
    {
        $user = User::factory()->create();
        $client = $this->client('Musterkunde', true, '2026-10-02');
        $this->invoice($user, $client, InvoiceType::Invoice, InvoiceStatus::Open, 125, 25);

        Livewire::actingAs($user)
            ->test(ClientDashboard::class)
            ->assertSee('Kundenübersicht')
            ->assertSee('Zeitraum')
            ->assertSee('125,00 €')
            ->call('selectRanking', 'invoice_count')
            ->assertSet('rankingMetric', 'invoice_count')
            ->assertSee('1 Rechnung')
            ->call('selectPeriod', 'this_year')
            ->assertSet('startDate', '2026-01-01')
            ->assertSet('endDate', '2026-12-31')
            ->set('startDate', '2026-11-01')
            ->set('endDate', '2026-10-01')
            ->call('applyCustomPeriod')
            ->assertHasErrors(['endDate']);
    }

    public function test_client_list_keeps_its_table_and_displays_the_dashboard(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListClients::class)
            ->assertSeeLivewire(ClientDashboard::class)
            ->assertTableColumnExists('client_number')
            ->assertTableFilterExists('client_name')
            ->assertTableFilterExists('client_date_created');
    }

    private function client(string $name, bool $active, string $createdAt): Client
    {
        $client = Client::query()->create([
            'client_name' => $name,
            'client_active' => $active,
        ]);

        $client->timestamps = false;
        $client->forceFill([
            'client_date_created' => $createdAt.' 10:00:00',
            'client_date_modified' => $createdAt.' 10:00:00',
        ])->saveQuietly();

        return $client;
    }

    private function invoice(
        User $user,
        Client $client,
        InvoiceType $type,
        InvoiceStatus $status,
        float $total,
        float $balance,
        bool $archived = false,
    ): Invoice {
        $invoice = Invoice::query()->create([
            'invoice_type' => $type,
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'invoice_group_id' => 3,
            'invoice_status_id' => $status,
            'is_read_only' => $status !== InvoiceStatus::Draft,
            'is_archived' => $archived,
            'invoice_date_created' => '2026-10-10',
            'invoice_time_created' => '12:00:00',
            'invoice_date_due' => '2026-11-10',
            'invoice_number' => $status === InvoiceStatus::Draft ? null : 'RE-'.Str::upper(Str::random(8)),
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => Str::random(32),
            'payment_method' => 0,
            'invoice_einvoicing_version' => '',
        ]);

        $invoice->amount()->create([
            'invoice_sign' => (string) $type->sign(),
            'invoice_item_subtotal' => $total,
            'invoice_item_tax_total' => 0,
            'invoice_tax_total' => 0,
            'invoice_total' => $total,
            'invoice_paid' => $total - $balance,
            'invoice_balance' => $balance,
        ]);

        return $invoice;
    }
}
