<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_uses_fidoma_branding(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Fidoma')
            ->assertSee('images/brand/fidoma-mark.png', escape: false)
            ->assertSee('Willkommen zurück')
            ->assertSee('ERP, klar organisiert')
            ->assertSee('Mehr Überblick.')
            ->assertSee('Kunden, Rechnungen, Belege und Finanzen')
            ->assertSee('Rechnungen & Angebote', escape: false)
            ->assertSee('Bank & Zahlungen', escape: false)
            ->assertSee('Belege & Dokumente', escape: false)
            ->assertSee('Mit automatisiertem Zuordnungsprozess und entsprechender Trefferquote.');
    }
}
