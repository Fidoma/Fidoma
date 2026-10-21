<?php

namespace Tests\Feature\Filament;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Contracts\Filament\Resources\ContractTemplates\ContractTemplateResource;
use App\Features\Documents\Services\PaperlessClient;
use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\Families\Filament\Resources\Families\FamilyResource;
use App\Features\Imports\Filament\Pages\DataImport;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use App\Features\Invoices\Filament\Pages\InvoiceLayout;
use App\Features\Invoices\Filament\Resources\Invoices\InvoiceResource;
use App\Features\Products\Filament\Resources\Products\ProductResource;
use App\Features\Quotes\Filament\Resources\Quotes\QuoteResource;
use App\Features\Settings\Filament\Clusters\SettingsCluster;
use App\Features\Settings\Filament\Pages\Modules;
use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use App\Features\Terminals\Filament\Pages\Terminal;
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

    public function test_external_integrations_share_the_external_services_navigation_group(): void
    {
        $this->assertSame('Externe Dienste', DataImport::getNavigationGroup());
        $this->assertSame('Externe Dienste', Terminal::getNavigationGroup());

        $this->actingAs(User::factory()->create());
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $externalServiceLabels = collect($panel->getNavigation())
            ->firstWhere(fn ($group): bool => $group->getLabel() === 'Externe Dienste')
            ?->getItems()
            ->map(fn ($item): string => $item->getLabel())
            ->all() ?? [];

        $this->assertContains('Datenimport', $externalServiceLabels);
        $this->assertContains('Terminals', $externalServiceLabels);
        $this->assertNotContains('Zahlungshistorie', $externalServiceLabels);
        $this->assertSame('Datenimport', array_last($externalServiceLabels));

        $navigationLabels = collect($panel->getNavigation())
            ->flatMap(fn ($group) => $group->getItems())
            ->map(fn ($item): string => $item->getLabel())
            ->all();

        $this->assertSame('Datenimport', array_last($navigationLabels));
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
        $this->assertNotContains('Vertragsvorlagen', $navigationLabels);
        $this->assertSame(SettingsCluster::class, ContractTemplateResource::getCluster());

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
        $this->assertSame(['profile', 'language'], array_keys($groups[0]));
        $this->assertSame(['settings', 'logout'], array_keys($groups[1]));
        $this->assertFalse(SettingsCluster::shouldRegisterNavigation());

        $this->get(SettingsCluster::getUrl())
            ->assertRedirect(Modules::getUrl());

        $this->get(InvoiceLayout::getUrl())
            ->assertOk()
            ->assertSeeTextInOrder([
                Modules::getNavigationLabel(),
                'PDF-Layout',
                EmailTemplateResource::getNavigationLabel(),
                ContractTemplateResource::getNavigationLabel(),
                InvoiceGroupResource::getNavigationLabel(),
                TaxRateResource::getNavigationLabel(),
                UserResource::getNavigationLabel(),
            ]);
    }

    public function test_user_can_switch_the_admin_locale_from_the_user_menu(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.locale.update', ['locale' => 'en']))
            ->assertRedirect();

        $this->assertSame('en', session('locale'));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('German')
            ->assertSee('English')
            ->assertSee('EN')
            ->assertSee('Settings')
            ->assertSee('Sign out')
            ->assertSee('Clients')
            ->assertSee('Quotes')
            ->assertSee('Invoices')
            ->assertSee('Projects')
            ->assertSee('Contracts')
            ->assertSee('Finance')
            ->assertSee('Bank transactions')
            ->assertSee('Assignments')
            ->assertSee('Payments')
            ->assertSee('Reports')
            ->assertSee('Data import')
            ->assertSee('External services')
            ->assertSee('From')
            ->assertSee('To')
            ->assertSee('Search')
            ->assertDontSee('Abmelden');

        $this->get(ClientResource::getUrl())
            ->assertOk()
            ->assertSee('All')
            ->assertSee('Active')
            ->assertSee('Archived')
            ->assertSee('Modified')
            ->assertDontSee('Geändert');

        $this->get(ClientResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Address')
            ->assertSee('Street and house number')
            ->assertSee('Master data')
            ->assertSee('Billing and tax details')
            ->assertSee('Enable e-invoicing')
            ->assertDontSee('Straße und Hausnummer');

        $this->get(InvoiceResource::getUrl())
            ->assertOk()
            ->assertSee('All')
            ->assertSee('Draft')
            ->assertSee('Open')
            ->assertSee('Paid')
            ->assertSee('Overdue')
            ->assertSee('Archived')
            ->assertSee('Billing type')
            ->assertSee('Due date')
            ->assertSee('Outstanding')
            ->assertDontSee('Überfällig');

        $this->get(InvoiceResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Billing and project')
            ->assertSee('Invoice date')
            ->assertSee('Items')
            ->assertSee('Quantity')
            ->assertSee('Unit price')
            ->assertSee('Payment terms')
            ->assertDontSee('Positionen');

        $this->get(QuoteResource::getUrl())
            ->assertOk()
            ->assertSee('All')
            ->assertSee('Draft')
            ->assertSee('Sent')
            ->assertSee('Accepted')
            ->assertSee('Rejected')
            ->assertSee('Cancelled')
            ->assertSee('Archived')
            ->assertSee('Quote number')
            ->assertSee('Valid until')
            ->assertDontSee('Angebotsnummer');

        $this->get(QuoteResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Quote date')
            ->assertSee('Items')
            ->assertSee('Quantity')
            ->assertSee('Unit price')
            ->assertSee('Quote terms')
            ->assertDontSee('Positionen');

        $this->get(ContractTemplateResource::getUrl())
            ->assertOk()
            ->assertSee('Contract templates')
            ->assertSee('Template title')
            ->assertSee('Modified')
            ->assertDontSee('Vertragsvorlagen');

        $this->get(ContractTemplateResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Template title')
            ->assertSee('Contract text')
            ->assertSee('Placeholders')
            ->assertDontSee('Vorlagentitel');

        $this->actingAs($user)
            ->post(route('admin.locale.update', ['locale' => 'de']))
            ->assertRedirect();

        $this->assertSame('de', session('locale'));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Deutsch')
            ->assertSee('Englisch')
            ->assertSee('Einstellungen')
            ->assertSee('Abmelden')
            ->assertDontSee('Sign out');
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
