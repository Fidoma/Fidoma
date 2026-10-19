<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_presents_fidoma_and_links_to_the_admin_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Fidoma – ERP, klar organisiert')
            ->assertSee('Mehr Zeit fürs Geschäft.')
            ->assertSee('Vom Bankumsatz zum passenden Vorgang.')
            ->assertSee('Rechnungen & Angebote', escape: false)
            ->assertSee('Mit automatisiertem Zuordnungsprozess und entsprechender Trefferquote.')
            ->assertSee('Hotel & Reservierungen')
            ->assertSee('Projekte & Abschlagsrechnungen')
            ->assertSee('Individuelle Verträge & Berichte')
            ->assertDontSee('>Auswertungen<', escape: false)
            ->assertSee('Umsatzsteuervoranmeldung')
            ->assertSee('Umsatzsteuerwerte periodenbezogen aufbereiten')
            ->assertSee('Fidoma läuft dort, wo Sie es möchten.')
            ->assertSee('/admin/login', escape: false)
            ->assertSee('images/brand/fidoma-mark.png', escape: false);
    }
}
