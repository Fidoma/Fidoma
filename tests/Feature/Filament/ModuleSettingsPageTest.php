<?php

namespace Tests\Feature\Filament;

use App\Features\Clients\Filament\Resources\Clients\ClientResource;
use App\Features\Settings\Filament\Pages\Modules;
use App\Features\Webmail\Services\WebmailArchiveSettings;
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

        DB::table('settings')->where('setting_key', 'like', 'module_enabled_%')->delete();
        DB::table('settings')->where('setting_key', 'like', 'webmail_zip_%')->delete();
    }

    protected function tearDown(): void
    {
        DB::table('settings')->where('setting_key', 'like', 'module_enabled_%')->delete();
        DB::table('settings')->where('setting_key', 'like', 'webmail_zip_%')->delete();

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
                'Tourismusstatistik',
                'Datenimport',
                'Webmail',
                'ZIP-Absenderadresse',
                'ZIP-Passwort',
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
        DB::table('settings')->insert([
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

        $this->assertDatabaseHas('settings', [
            'setting_key' => 'module_enabled_clients',
            'setting_value' => '0',
        ]);
        $this->assertFalse(ClientResource::shouldRegisterNavigation());
    }

    public function test_webmail_zip_rule_is_saved_with_an_encrypted_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Modules::class)
            ->set('data.webmail_archive.sender', 'Archive@Example.Test')
            ->set('data.webmail_archive.password', 'zip-secret')
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Moduleinstellungen gespeichert');

        $storedPassword = DB::table('settings')
            ->where('setting_key', 'webmail_zip_password')
            ->value('setting_value');

        $this->assertIsString($storedPassword);
        $this->assertNotSame('zip-secret', $storedPassword);
        $this->assertSame([
            'sender' => 'archive@example.test',
            'password' => 'zip-secret',
        ], app(WebmailArchiveSettings::class)->rule());

        Livewire::actingAs($user)
            ->test(Modules::class)
            ->assertSet('data.webmail_archive.sender', 'archive@example.test')
            ->assertSet('data.webmail_archive.password', '');
    }

    public function test_blank_webmail_zip_password_keeps_the_existing_secret(): void
    {
        $user = User::factory()->create();
        app(WebmailArchiveSettings::class)->save([
            'sender' => 'archive@example.test',
            'password' => 'zip-secret',
        ]);

        Livewire::actingAs($user)
            ->test(Modules::class)
            ->set('data.webmail_archive.sender', 'new@example.test')
            ->set('data.webmail_archive.password', '')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([
            'sender' => 'new@example.test',
            'password' => 'zip-secret',
        ], app(WebmailArchiveSettings::class)->rule());
    }

    public function test_new_webmail_zip_sender_requires_a_password(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Modules::class)
            ->set('data.webmail_archive.sender', 'archive@example.test')
            ->call('save')
            ->assertHasFormErrors(['webmail_archive.password' => 'required']);

        $this->assertNull(app(WebmailArchiveSettings::class)->rule());
    }

    public function test_webmail_zip_sender_accepts_an_exact_domain_wildcard(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Modules::class)
            ->set('data.webmail_archive.sender', '*@Google.DE')
            ->set('data.webmail_archive.password', 'zip-secret')
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(WebmailArchiveSettings::class);

        $this->assertSame('*@google.de', $settings->rule()['sender'] ?? null);
        $this->assertTrue($settings->matchesSender('rechnung@google.de'));
        $this->assertTrue($settings->matchesSender('INFO@GOOGLE.DE'));
        $this->assertFalse($settings->matchesSender('rechnung@mail.google.de'));
        $this->assertFalse($settings->matchesSender('rechnung@notgoogle.de'));
    }

    public function test_webmail_zip_sender_rejects_arbitrary_wildcards(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Modules::class)
            ->set('data.webmail_archive.sender', 'invoice*@google.de')
            ->set('data.webmail_archive.password', 'zip-secret')
            ->call('save')
            ->assertHasFormErrors(['webmail_archive.sender']);

        $this->assertNull(app(WebmailArchiveSettings::class)->rule());
    }
}
