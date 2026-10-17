<?php

namespace Tests\Feature\Filament;

use App\Features\Webmail\Filament\Pages\Webmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebmailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/webmail')->assertRedirect('/admin/login');
        $this->get('/admin/webmail/client')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_sees_the_install_state(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Webmail::class)
            ->assertSet('installed', false)
            ->assertSee('Roundcube ist noch nicht installiert')
            ->assertActionVisible('install');
    }

    public function test_gateway_rejects_an_uninstalled_roundcube(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/webmail/client')
            ->assertStatus(503);
    }
}
