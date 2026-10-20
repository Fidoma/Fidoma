<?php

namespace Tests\Feature;

use Tests\TestCase;

class PricingPageTest extends TestCase
{
    public function test_pricing_page_presents_the_available_models_without_inventing_links(): void
    {
        config()->set('website.links.cloud');
        config()->set('website.links.support');
        config()->set('website.links.contact');
        config()->set('website.links.donation');

        $this->get('/preise')
            ->assertOk()
            ->assertSee('Fidoma Preise – Community, Cloud und Support')
            ->assertSee('Transparent. Fair.')
            ->assertSee('Fidoma Community')
            ->assertSee('0 €')
            ->assertSee('Fidoma Cloud')
            ->assertSee('19 €')
            ->assertSee('Fidoma Support')
            ->assertSee('ab 11 €')
            ->assertSee('Support kann unabhängig vom Hosting angefragt werden.')
            ->assertSee('Fidoma gefällt Ihnen? Unterstützen Sie das Projekt.')
            ->assertSee('Ein Zahlungsweg ist noch nicht veröffentlicht.')
            ->assertSee('Der direkte Kontaktweg ist noch nicht veröffentlicht')
            ->assertSee('Kann ich später von Self-Hosting zur Cloud wechseln?')
            ->assertDontSee('href=""', escape: false);
    }

    public function test_configured_contact_and_project_links_are_used(): void
    {
        config()->set('website.links.cloud', 'https://example.test/cloud');
        config()->set('website.links.support', 'https://example.test/support');
        config()->set('website.links.contact', 'https://example.test/contact');
        config()->set('website.links.donation', 'https://example.test/donation');

        $this->get('/preise')
            ->assertOk()
            ->assertSee('https://example.test/cloud', escape: false)
            ->assertSee('https://example.test/support', escape: false)
            ->assertSee('https://example.test/contact', escape: false)
            ->assertSee('https://example.test/donation', escape: false);
    }
}
