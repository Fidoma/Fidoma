<?php

namespace Tests\Feature;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Quotes\Actions\ArchiveQuote;
use App\Features\Quotes\Enums\QuoteStatus;
use App\Features\Quotes\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Features\Quotes\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSeeText('Archivieren')
            ->assertDontSeeText('Löschen');
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
