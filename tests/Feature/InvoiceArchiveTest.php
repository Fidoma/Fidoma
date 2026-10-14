<?php

namespace Tests\Feature;

use App\Features\Invoices\Actions\ArchiveInvoice;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Enums\InvoiceType;
use App\Features\Invoices\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_an_invoice_keeps_the_record_and_makes_it_read_only(): void
    {
        $invoice = $this->createInvoice();

        app(ArchiveInvoice::class)->handle($invoice);

        $this->assertDatabaseHas('ip_invoices', [
            'invoice_id' => $invoice->getKey(),
            'is_archived' => true,
            'is_read_only' => true,
        ]);
    }

    public function test_invoice_index_offers_archiving_instead_of_deletion(): void
    {
        $user = User::factory()->create();
        $this->createInvoice();

        $this->actingAs($user)
            ->get('/admin/invoices')
            ->assertOk()
            ->assertSeeText('Archiviert')
            ->assertSeeText('Archivieren')
            ->assertDontSeeText('Löschen');
    }

    private function createInvoice(): Invoice
    {
        return Invoice::query()->create([
            'invoice_type' => InvoiceType::Invoice,
            'user_id' => 1,
            'client_id' => 1,
            'invoice_group_id' => 1,
            'invoice_status_id' => InvoiceStatus::Draft,
            'is_read_only' => false,
            'invoice_date_created' => '2026-10-13',
            'invoice_time_created' => '12:00:00',
            'invoice_date_due' => '2026-11-12',
            'invoice_number' => 'RE-TEST-1',
            'invoice_discount_amount' => 0,
            'invoice_discount_percent' => 0,
            'invoice_terms' => '',
            'invoice_url_key' => Str::random(32),
            'payment_method' => 0,
            'invoice_einvoicing_version' => '',
        ]);
    }
}
