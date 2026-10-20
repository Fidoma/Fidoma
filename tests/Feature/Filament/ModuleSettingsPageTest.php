<?php

namespace Tests\Feature\Filament;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Settings\Filament\Pages\Modules;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('ip_settings')->where('setting_key', 'like', 'module_enabled_%')->delete();
    }

    protected function tearDown(): void
    {
        DB::table('ip_settings')->where('setting_key', 'like', 'module_enabled_%')->delete();

        parent::tearDown();
    }

    public function test_module_settings_are_available_in_the_settings_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(Modules::getUrl())
            ->assertOk()
            ->assertSeeTextInOrder([
                'Kunden',
                'Rechnungen',
                'Angebote',
                'Produkte',
                'Projekte',
                'Verträge',
                'Hotel & Reservierungen',
                'Banktransaktionen',
                'Zuordnungen',
                'Zahlungen',
                'Berichte',
                'Datenimport',
                'Webmail',
                'Paperless-ngx',
            ]);

        $this->assertNull(
            Livewire::actingAs($user)
                ->test(Modules::class)
                ->instance()
                ->getHeading(),
        );
    }

    public function test_disabled_module_is_removed_from_the_main_navigation(): void
    {
        DB::table('ip_settings')->insert([
            'setting_key' => 'module_enabled_clients',
            'setting_value' => '0',
        ]);

        $this->assertFalse(ClientResource::shouldRegisterNavigation());
    }

    public function test_module_selection_is_persisted(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Modules::class)
            ->set('data.modules.clients', false)
            ->call('save')
            ->assertNotified('Moduleinstellungen gespeichert');

        $this->assertDatabaseHas('ip_settings', [
            'setting_key' => 'module_enabled_clients',
            'setting_value' => '0',
        ]);
        $this->assertFalse(ClientResource::shouldRegisterNavigation());
    }
}
