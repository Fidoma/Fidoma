<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_presents_fidoma_and_links_to_the_admin_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Fidoma – Open-Source-ERP mit intelligenter Automatisierung')
            ->assertSee('Mehr Zeit fürs Geschäft.')
            ->assertSee('Weniger Bürokratie.')
            ->assertSee('Weniger klicken. Mehr automatisch erledigen.')
            ->assertSee('Ihre Büroarbeit. Intelligent vernetzt.')
            ->assertSee('Eine eigenständige KI-Engine ist derzeit nicht Bestandteil von Fidoma.')
            ->assertSee('Rechnungen & Angebote', escape: false)
            ->assertSee('Hotellerie')
            ->assertSee('Handwerk & Bau', escape: false)
            ->assertSee('Terminal-Zahlungen')
            ->assertSee('FinTS-Banking')
            ->assertDontSee('Kunden & Produkte', escape: false)
            ->assertDontSee('>Auswertungen<', escape: false)
            ->assertSee('Ihre Software. Ihre Entscheidung.')
            ->assertSee('/preise', escape: false)
            ->assertSee('/admin/login', escape: false)
            ->assertSee('images/brand/fidoma-mark.png', escape: false);
    }
}
