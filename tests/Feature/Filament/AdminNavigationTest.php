<?php

namespace Tests\Feature\Filament;

use App\Features\EmailTemplates\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Features\InvoiceGroups\Filament\Resources\InvoiceGroups\InvoiceGroupResource;
use App\Features\TaxRates\Filament\Resources\TaxRates\TaxRateResource;
use App\Features\Users\Filament\Resources\Users\UserResource;
use App\Filament\Pages\InvoiceLayout;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_settings_are_in_the_user_menu_instead_of_the_sidebar(): void
    {
        $this->actingAs(User::factory()->create());

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $groups = $panel->getUserMenuItemGroups();

        $this->assertCount(2, $groups);
        $this->assertSame(['profile', 'logout'], array_keys($groups[0]));
        $this->assertSame(
            ['pdfLayouts', 'emailTemplates', 'invoiceGroups', 'taxRates', 'users'],
            array_keys($groups[1]),
        );

        $this->assertFalse(InvoiceLayout::shouldRegisterNavigation());
        $this->assertFalse(EmailTemplateResource::shouldRegisterNavigation());
        $this->assertFalse(InvoiceGroupResource::shouldRegisterNavigation());
        $this->assertFalse(TaxRateResource::shouldRegisterNavigation());
        $this->assertFalse(UserResource::shouldRegisterNavigation());
    }
}
