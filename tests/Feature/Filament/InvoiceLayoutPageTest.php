<?php

namespace Tests\Feature\Filament;

use App\Features\Invoices\Services\InvoiceTemplateRenderer;
use App\Filament\Pages\InvoiceLayout as InvoiceLayoutPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceLayoutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/invoice-layout')->assertRedirect('/admin/login');
    }

    public function test_layout_page_shows_editor_preview_and_sample_invoice(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/invoice-layout')
            ->assertOk()
            ->assertSee('Rechnungslayout')
            ->assertSee('PDF-Vorschau')
            ->assertSee('RE-2026-1042');
    }

    public function test_layout_can_be_saved_and_loaded_again(): void
    {
        $user = User::factory()->create();
        $html = '<h1>{{ invoice.number }}</h1><table><tbody>{{ items }}</tbody></table>';
        $css = 'h1 { color: navy; }';

        Livewire::actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->call('saveTemplate', $html, $css)
            ->assertNotified('Rechnungslayout gespeichert');

        $this->assertDatabaseHas('invoice_layouts', ['html' => $html, 'css' => $css]);

        Livewire::actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->assertSet('html', $html)
            ->assertSet('css', $css);
    }

    public function test_pdf_preview_uses_the_current_unsaved_draft(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(InvoiceLayoutPage::class)
            ->call('showPdfPreview', '<h1>{{ invoice.number }}</h1>', 'h1 { color: red; }')
            ->assertSet('pdfPreviewUrl', fn (?string $url): bool => str_contains($url ?? '', '/admin/invoice-layout/pdf-preview'));

        $this->actingAs($user)
            ->withSession(['invoice-layout-preview' => [
                'html' => '<h1>{{ invoice.number }}</h1>',
                'css' => 'h1 { color: red; }',
            ]])
            ->get(route('invoice-layout.pdf-preview'))
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
}
