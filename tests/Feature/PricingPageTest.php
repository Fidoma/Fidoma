<?php

namespace Tests\Feature;

use Tests\TestCase;

class PricingPageTest extends TestCase
{
    public function test_configured_contact_and_project_links_are_used(): void
    {
        config()->set('website.links.cloud', 'https://example.test/cloud');
        config()->set('website.links.support', 'https://example.test/support');
        config()->set('website.links.contact', 'https://example.test/contact');
        config()->set('website.links.donation', 'https://example.test/donation');
        config()->set('website.paypal.donation', 'https://paypal.test/donation');

        $this->get('/preise')
            ->assertOk()
            ->assertSee('https://example.test/cloud', escape: false)
            ->assertSee('https://example.test/support', escape: false)
            ->assertSee('https://example.test/contact', escape: false)
            ->assertSee('https://paypal.test/donation', escape: false)
            ->assertSee('https://example.test/cloud', escape: false)
            ->assertSee('https://example.test/support', escape: false);
    }
}
