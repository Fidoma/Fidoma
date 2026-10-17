<?php

namespace Tests\Feature\Filament;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Documents\Services\PaperlessClient;
use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\Families\Filament\Resources\Families\FamilyResource;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use App\Features\Invoices\Filament\Pages\InvoiceLayout;
use App\Features\Products\Filament\Resources\Products\ProductResource;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use App\Features\Units\Filament\Resources\Units\UnitResource;
use App\Features\Users\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_is_compact_and_collapses_to_an_icon_rail_on_desktop(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame('17rem', $panel->getSidebarWidth());
        $this->assertSame('4.5rem', $panel->getCollapsedSidebarWidth());
        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertFalse($panel->isSidebarFullyCollapsibleOnDesktop());
    }

    public function test_clients_navigation_uses_the_german_label_and_people_icon(): void
    {
        $this->assertSame('Kunden', ClientResource::getNavigationLabel());
        $this->assertSame(Heroicon::OutlinedUserGroup, ClientResource::getNavigationIcon());
    }

    public function test_products_resources_share_one_navigation_item_and_top_tabs(): void
    {
        $this->actingAs(User::factory()->create());

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $navigationLabels = collect($panel->getNavigation())
            ->flatMap(fn ($group) => $group->getItems())
            ->map(fn ($item): string => $item->getLabel())
            ->all();

        $this->assertContains('Angebote', $navigationLabels);
        $this->assertContains('Produkte', $navigationLabels);
        $this->assertLessThan(
            array_search('Produkte', $navigationLabels, true),
            array_search('Angebote', $navigationLabels, true),
        );
        $this->assertNotContains('Familien', $navigationLabels);
        $this->assertNotContains('Einheiten', $navigationLabels);

        $this->get(ProductResource::getUrl())
            ->assertOk()
            ->assertSeeTextInOrder(['Produkte', 'Familien', 'Einheiten']);

        $this->assertSame('Familien', FamilyResource::getNavigationLabel());
        $this->assertSame('Einheiten', UnitResource::getNavigationLabel());
        $this->assertSame('Angebote', QuoteResource::getNavigationLabel());
    }

    public function test_settings_are_in_the_user_menu_instead_of_the_sidebar(): void
    {
        $this->actingAs(User::factory()->create());

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $groups = $panel->getUserMenuItemGroups();

        $this->assertCount(2, $groups);
        $this->assertSame(['profile', 'logout'], array_keys($groups[0]));
        $this->assertSame(['settings'], array_keys($groups[1]));
        $this->assertFalse(SettingsCluster::shouldRegisterNavigation());

        $this->get(SettingsCluster::getUrl())
            ->assertRedirect(InvoiceLayout::getUrl());

        $this->get(InvoiceLayout::getUrl())
            ->assertOk()
            ->assertSeeTextInOrder([
                'PDF-Layout',
                EmailTemplateResource::getNavigationLabel(),
                InvoiceGroupResource::getNavigationLabel(),
                TaxRateResource::getNavigationLabel(),
                UserResource::getNavigationLabel(),
            ]);
    }

    public function test_paperless_documents_link_reuses_a_named_browser_tab(): void
    {
        Config::set('services.paperless.url', 'https://paperless.example.test');
        $this->actingAs(User::factory()->create());

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $item = collect($panel->getNavigation())
            ->firstWhere(fn ($group): bool => $group->getLabel() === 'Externe Dienste')
            ?->getItems()
            ->firstWhere(fn ($item): bool => $item->getLabel() === 'Dokumente');

        $this->assertNotNull($item);
        $this->assertSame('https://paperless.example.test', $item->getUrl());
        $this->assertStringContainsString('window.paperlessDocumentPreview.focus()', $item->getExtraAttributeBag()->get('x-on:click.prevent'));
        $this->assertStringContainsString("'".PaperlessClient::DOCUMENT_PREVIEW_WINDOW."'", $item->getExtraAttributeBag()->get('x-on:click.prevent'));
    }
}
