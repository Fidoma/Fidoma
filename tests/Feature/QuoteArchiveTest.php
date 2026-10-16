<?php

namespace Tests\Feature;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Clients\Models\Client;
use App\Features\Invoices\Enums\InvoiceStatus;
use App\Features\Invoices\Models\Invoice;
use App\Features\Quotes\Actions\ArchiveQuote;
use App\Features\Quotes\Actions\ConvertQuoteToInvoice;
use App\Features\Quotes\Actions\GenerateQuotePdf;
use App\Features\Quotes\Actions\RecalculateQuote;
use App\Features\Quotes\Actions\SendQuoteEmail;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Features\Quotes\Models\Quote;
use App\Features\TaxRates\Models\TaxRate;
use App\Features\Units\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_a_quote_keeps_the_record(): void
    {
        $quote = $this->createQuote();

        app(ArchiveQuote::class)->handle($quote);

        $this->assertDatabaseHas('ip_quotes', [
            'quote_id' => $quote->getKey(),
            'is_archived' => true,
        ]);
    }

    public function test_quote_index_uses_an_options_menu_and_offers_archiving_instead_of_deletion(): void
    {
        $user = User::factory()->create();
        $this->createQuote();

        $this->actingAs($user)
            ->get('/admin/quotes')
            ->assertOk()
            ->assertSeeText('Archiviert')
            ->assertSeeText('Bearbeiten')
            ->assertSeeText('In Rechnung umwandeln')
            ->assertSeeText('Angenommen')
            ->assertSeeText('Abgelehnt')
            ->assertSeeText('Storniert')
            ->assertSeeText('E-Mail versenden')
            ->assertSeeText('Archivieren')
            ->assertDontSeeText('Löschen');
    }

    public function test_quote_status_can_be_changed_from_the_options_menu(): void
    {
        $user = User::factory()->create();
        $quote = $this->createQuote();

        Livewire::actingAs($user)
            ->test(ListQuotes::class)
            ->callTableAction('markApproved', $quote)
            ->assertNotified('Angebot wurde als „Angenommen“ markiert.')
            ->assertTableActionHidden('markApproved', $quote->refresh());

        $this->assertSame(QuoteStatus::Approved, $quote->refresh()->quote_status_id);

        Livewire::actingAs($user)
            ->test(ListQuotes::class)
            ->callTableAction('markRejected', $quote)
            ->assertNotified('Angebot wurde als „Abgelehnt“ markiert.')
            ->assertTableActionHidden('markRejected', $quote->refresh());

        $this->assertSame(QuoteStatus::Rejected, $quote->refresh()->quote_status_id);

        Livewire::actingAs($user)
            ->test(ListQuotes::class)
            ->callTableAction('markCanceled', $quote)
            ->assertNotified('Angebot wurde als „Storniert“ markiert.')
            ->assertTableActionHidden('markCanceled', $quote->refresh());

        $this->assertSame(QuoteStatus::Canceled, $quote->refresh()->quote_status_id);
    }

    public function test_only_quote_number_and_client_columns_are_linked(): void
    {
        $user = User::factory()->create();
        $quote = $this->createQuote();

        $component = Livewire::actingAs($user)
            ->test(ListQuotes::class)
            ->assertCanSeeTableRecords([$quote])
            ->assertTableColumnExists(
                'quote_number',
                fn ($column): bool => $column->getUrl() === QuoteResource::getUrl('edit', ['record' => $quote]),
                $quote,
            )
            ->assertTableColumnExists(
                'client.client_name',
                fn ($column): bool => $column->getUrl() === ClientResource::getUrl('view', ['record' => $quote->client_id]),
                $quote,
            )
            ->assertTableColumnExists(
                'quote_date_created',
                fn ($column): bool => $column->getUrl() === null,
                $quote,
            );

        $this->assertNull($component->instance()->getTable()->getRecordUrl($quote));
    }

    public function test_quote_can_be_rendered_and_sent_as_an_email(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'array',
            'mail.mailers.smtp.host' => 'smtp.test',
            'mail.from.address' => 'angebote@example.com',
            'mail.from.name' => 'Fidoma',
        ]);
        $user = User::factory()->create();
        $quote = $this->createQuote();

        $this->assertStringStartsWith('%PDF', app(GenerateQuotePdf::class)->handle($quote)->output());

        $this->actingAs($user);
        app(SendQuoteEmail::class)->handle($quote, [
            'to' => 'kunde@example.com',
            'from_name' => 'Fidoma',
            'from_email' => 'angebote@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Ihr Angebot',
            'body' => '<p>Guten Tag</p>',
            'attach_pdf' => true,
        ]);

        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertSame(QuoteStatus::Sent, $quote->refresh()->quote_status_id);
        $this->assertDatabaseHas('ip_audit_logs', [
            'event_type' => 'quote_smtp_accepted',
            'entity_type' => 'quote',
            'entity_id' => $quote->getKey(),
            'status' => 'success',
        ]);
    }

    public function test_quote_is_converted_to_one_invoice_with_its_items_and_taxes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $client = Client::query()->create([
            'client_name' => 'Angebotskunde',
            'client_active' => true,
        ]);
        $taxRate = TaxRate::query()->firstOrFail();
        $unit = Unit::query()->firstOrFail();
        $quote = $this->createQuote();
        $quote->update([
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'quote_status_id' => QuoteStatus::Sent,
            'quote_discount_amount' => 10,
            'notes' => 'Zahlbar innerhalb von 14 Tagen.',
        ]);
        $quote->items()->create([
            'item_tax_rate_id' => $taxRate->getKey(),
            'item_tax_included' => false,
            'item_name' => 'Beratung',
            'item_description' => 'Projektberatung',
            'item_quantity' => 2,
            'item_price' => 100,
            'item_discount_amount' => 0,
            'item_order' => 1,
            'item_product_unit_id' => $unit->getKey(),
        ]);
        $quote->quoteTaxRates()->create([
            'tax_rate_id' => $taxRate->getKey(),
            'include_item_tax' => false,
            'quote_tax_rate_amount' => 0,
        ]);
        app(RecalculateQuote::class)->handle($quote);
        $quote->refresh();

        $invoiceCount = Invoice::query()->count();
        $firstInvoice = app(ConvertQuoteToInvoice::class)->handle($quote);
        $secondInvoice = app(ConvertQuoteToInvoice::class)->handle($quote->refresh());

        $this->assertTrue($firstInvoice->is($secondInvoice));
        $this->assertSame($invoiceCount + 1, Invoice::query()->count());
        $this->assertSame(1, $firstInvoice->items()->count());
        $this->assertSame(1, $firstInvoice->invoiceTaxRates()->count());
        $this->assertSame($quote->amount->quote_total, $firstInvoice->amount->invoice_total);
        $this->assertSame(InvoiceStatus::Draft, $firstInvoice->invoice_status_id);
        $this->assertSame('Zahlbar innerhalb von 14 Tagen.', $firstInvoice->invoice_terms);
        $this->assertSame($firstInvoice->getKey(), $quote->refresh()->invoice_id);
        $this->assertSame(QuoteStatus::Approved, $quote->quote_status_id);
    }

    private function createQuote(): Quote
    {
        return Quote::query()->create([
            'invoice_id' => 0,
            'user_id' => 1,
            'client_id' => 1,
            'invoice_group_id' => 1,
            'quote_status_id' => QuoteStatus::Draft,
            'quote_date_created' => '2026-10-13',
            'quote_date_expires' => '2026-11-12',
            'quote_number' => 'ANG-TEST-'.Str::random(6),
            'quote_discount_amount' => 0,
            'quote_discount_percent' => 0,
            'quote_url_key' => Str::random(32),
            'notes' => '',
        ]);
    }
}
