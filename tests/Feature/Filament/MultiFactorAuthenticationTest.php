<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_provides_recoverable_authenticator_app_authentication(): void
    {
        $panel = Filament::getPanel('admin');
        $providers = $panel->getMultiFactorAuthenticationProviders();

        $this->assertTrue($panel->hasProfile());
        $this->assertArrayHasKey('app', $providers);
        $this->assertInstanceOf(AppAuthentication::class, $providers['app']);
        $this->assertTrue($providers['app']->isRecoverable());
        $this->assertFalse($panel->isMultiFactorAuthenticationRequired());
    }

    public function test_user_stores_authenticator_secret_and_recovery_codes_encrypted(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(HasAppAuthentication::class, $user);
        $this->assertInstanceOf(HasAppAuthenticationRecovery::class, $user);

        $user->saveAppAuthenticationSecret('TOTP-SECRET');
        $user->saveAppAuthenticationRecoveryCodes([
            Hash::make('recovery-code'),
        ]);

        $user->refresh();

        $this->assertSame('TOTP-SECRET', $user->getAppAuthenticationSecret());
        $this->assertTrue(Hash::check('recovery-code', $user->getAppAuthenticationRecoveryCodes()[0]));
        $this->assertNotSame('TOTP-SECRET', $user->getRawOriginal('app_authentication_secret'));
        $this->assertStringNotContainsString('recovery-code', $user->getRawOriginal('app_authentication_recovery_codes'));
        $this->assertArrayNotHasKey('app_authentication_secret', $user->toArray());
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $user->toArray());
    }

    public function test_user_can_open_authenticator_app_setup_from_the_profile(): void
    {
        $user = User::factory()->create();
        $panel = Filament::getPanel('admin');

        Filament::setCurrentPanel($panel);

        $this->actingAs($user)
            ->get($panel->getProfileUrl())
            ->assertOk()
            ->assertSeeText('Zwei-Faktor-Authentifizierung (2FA)')
            ->assertSeeText('Authenticator-App')
            ->assertSeeText('Einrichten');
    }
}
