<?php

namespace Tests\Feature\Filament;

use App\Features\Invoices\Filament\Pages\InvoiceLayout as InvoiceLayoutPage;
use App\Features\Invoices\Models\InvoiceLayout;
use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceLayoutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(InvoiceLayoutPage::getUrl())->assertRedirect('/admin/login');
    }

    public function test_layout_page_shows_editor_preview_and_sample_invoice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(InvoiceLayoutPage::getUrl())
            ->assertOk()
            ->assertSee('Rechnungen')
            ->assertSee('Angebote')
            ->assertSee('Reservierungen')
            ->assertSee('PDF-Vorschau')
            ->assertSee('Beispielwert')
            ->assertSee('{{ invoice.number }}')
            ->assertSee('Musterwerk GmbH')
            ->assertSee('RE-2026-1042');

        $this->assertNull(
            Livewire::actingAs($user)
                ->test(InvoiceLayoutPage::class)
                ->instance()
                ->getHeading(),
        );
    }

    public function test_layout_can_be_saved_and_loaded_again(): void
    {
        $user = User::factory()->create();
        $html = '<h1>{{ invoice.number }}</h1><table><tbody>{{ items }}</tbody></table>';
        $css = 'h1 { color: navy; }';

        Livewire::actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->call('saveTemplate', $html, $css)
            ->assertNotified('PDF-Layout gespeichert');

        $this->assertDatabaseHas('invoice_layouts', ['html' => $html, 'css' => $css]);

        Livewire::actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->assertSet('html', $html)
            ->assertSet('css', $css);
    }

    public function test_live_preview_does_not_save_the_draft(): void
    {
        $user = User::factory()->create();
        InvoiceLayout::query()->create(['html' => '<h1>Gespeichert</h1>', 'css' => '']);

        Livewire::actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->call('updatePreview', '<h1>{{ invoice.number }}</h1>', 'h1 { color: red; }')
            ->assertDispatched('invoice-layout-preview-updated');

        $this->assertDatabaseHas('invoice_layouts', ['html' => '<h1>Gespeichert</h1>', 'css' => '']);
        $this->assertDatabaseMissing('invoice_layouts', ['html' => '<h1>{{ invoice.number }}</h1>']);
    }

    public function test_each_document_type_has_its_own_layout_and_placeholders(): void
    {
        $user = User::factory()->create();

        Livewire::withQueryParams(['type' => 'quote'])
            ->actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->assertSet('activeType', 'quote')
            ->assertSee('{{ quote.number }}')
            ->call('saveTemplate', '<h1>Angebot {{ quote.number }}</h1>', '');

        Livewire::withQueryParams(['type' => 'reservation'])
            ->actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->assertSet('activeType', 'reservation')
            ->assertSee('{{ reservation.number }}')
            ->assertSee('{{ reservation.arrival_date }}')
            ->call('saveTemplate', '<h1>Reservierung {{ reservation.number }}</h1>', '');

        $this->assertDatabaseHas('invoice_layouts', [
            'type' => 'quote',
            'html' => '<h1>Angebot {{ quote.number }}</h1>',
        ]);
        $this->assertDatabaseHas('invoice_layouts', [
            'type' => 'reservation',
            'html' => '<h1>Reservierung {{ reservation.number }}</h1>',
        ]);
    }

    public function test_pdf_preview_uses_the_current_unsaved_draft(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->call('showPdfPreview', '<h1>{{ invoice.number }}</h1>', 'h1 { color: red; }')
            ->assertSet('pdfPreviewUrl', fn (?string $url): bool => str_contains($url ?? '', '/admin/pdf-layouts/pdf-preview'));

        $this->actingAs($user)
            ->withSession(['pdf-layout-preview' => [
                'type' => 'invoice',
                'html' => '<h1>{{ invoice.number }}</h1>',
                'css' => 'h1 { color: red; }',
            ]])
            ->get(route('pdf-layouts.pdf-preview'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_renderer_replaces_placeholders_without_executing_custom_code(): void
    {
        $rendered = app(InvoiceTemplateRenderer::class)->render(
            '<script>alert(1)</script><?php echo "unsafe"; ?><h1>{{ invoice.number }}</h1><p onclick="alert(1)">{{ client.name }}</p>',
            'h1 { color: red; }',
            ['invoice' => ['number' => '<RE-1>'], 'client' => ['name' => '<b>Kunde</b>'], 'items' => [], 'totals' => []],
        );

        $this->assertStringContainsString('&lt;RE-1&gt;', $rendered);
        $this->assertStringContainsString('&lt;b&gt;Kunde&lt;/b&gt;', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringNotContainsString('<?php', $rendered);
        $this->assertStringNotContainsString('onclick=', $rendered);
    }

    public function test_item_rows_include_the_net_total_after_the_unit_price(): void
    {
        $rendered = app(InvoiceTemplateRenderer::class)->render(
            '<table><tbody>{{ items }}</tbody></table>',
            '',
            ['items' => [[
                'name' => 'Leistung', 'description' => '', 'quantity' => 2, 'unit' => 'Std.',
                'price' => 100, 'net_total' => 200, 'tax' => 19, 'total' => 238,
            ]]],
        );

        $this->assertMatchesRegularExpression('/100,00 €<\/td><td class="number">200,00 €<\/td><td class="number">19,00 %/', $rendered);
    }

    public function test_tax_rows_are_rendered_separately_by_rate(): void
    {
        $rendered = app(InvoiceTemplateRenderer::class)->render(
            '<table><tbody>{{ tax_rows }}</tbody></table>',
            '',
            ['items' => [], 'tax_rows' => [
                ['name' => 'Umsatzsteuer', 'rate' => 7, 'amount' => 7],
                ['name' => 'Umsatzsteuer', 'rate' => 19, 'amount' => 38],
            ], 'totals' => ['tax' => 45]],
        );

        $this->assertStringContainsString('<td>Umsatzsteuer 7 %</td><td>7,00 €</td>', $rendered);
        $this->assertStringContainsString('<td>Umsatzsteuer 19 %</td><td>38,00 €</td>', $rendered);
    }

    public function test_tax_rate_is_not_duplicated_when_the_tax_name_already_contains_it(): void
    {
        $rendered = app(InvoiceTemplateRenderer::class)->render(
            '<table><tbody>{{ tax_rows }}</tbody></table>',
            '',
            ['items' => [], 'tax_rows' => [
                ['name' => '7%', 'rate' => 7, 'amount' => 7],
                ['name' => '19 %', 'rate' => 19, 'amount' => 38],
            ], 'totals' => ['tax' => 45]],
        );

        $this->assertStringContainsString('<td>Steuer 7 %</td>', $rendered);
        $this->assertStringContainsString('<td>Steuer 19 %</td>', $rendered);
        $this->assertStringNotContainsString('7% 7 %', $rendered);
        $this->assertStringNotContainsString('19 % 19 %', $rendered);
    }

    public function test_single_tax_rate_replaces_the_redundant_total_tax_row(): void
    {
        $rendered = app(InvoiceTemplateRenderer::class)->render(
            '<table><tbody>{{ tax_rows }}<tr><td>Umsatzsteuer</td><td>{{ totals.tax }}</td></tr></tbody></table>',
            '',
            [
                'items' => [],
                'tax_rows' => [['name' => '7%', 'rate' => 7, 'amount' => 586.25]],
                'totals' => ['tax' => 586.25],
            ],
        );

        $this->assertStringContainsString('Steuer 7 %', $rendered);
        $this->assertStringNotContainsString('Umsatzsteuer</td>', $rendered);
        $this->assertSame(1, substr_count($rendered, '586,25 €'));
    }
}
