<?php

namespace Tests\Unit\Features\Webmail;

use App\Features\Webmail\Services\RoundcubeConfiguration;
use App\Features\Webmail\Services\RoundcubeInstaller;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class RoundcubeConfigurationTest extends TestCase
{
    public function test_it_maps_imap_and_smtp_configuration_without_modifying_secrets(): void
    {
        Config::set('webmail.imap', [
            'host' => 'imap.example.test',
            'port' => 993,
            'scheme' => 'ssl',
            'username' => 'office@example.test',
            'password' => 'imap-<>secret',
            'mailbox' => 'INBOX',
            'verify_certificates' => true,
        ]);
        Config::set('webmail.smtp_verify_certificates', true);
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', [
            'host' => 'smtp.example.test',
            'port' => 587,
            'scheme' => 'tls',
            'username' => 'mailer@example.test',
            'password' => 'smtp-secret',
        ]);
        Config::set('mail.from.address', 'office@example.test');

        $context = app(RoundcubeConfiguration::class)->build();

        $this->assertSame('ssl://imap.example.test:993', $context['config']['imap_host']);
        $this->assertSame('tls://smtp.example.test:587', $context['config']['smtp_host']);
        $this->assertSame('office@example.test', $context['username']);
        $this->assertSame('imap-<>secret', $context['password']);
        $this->assertSame('smtp-secret', $context['config']['smtp_pass']);
        $this->assertTrue($context['config']['imap_conn_options']['ssl']['verify_peer']);
        $this->assertSame('fidoma', $context['config']['skin']);
        $this->assertSame('/images/fidoma-mark.png', $context['config']['skin_logo']['fidoma:*']);
        $this->assertSame('/images/fidoma-mark.png', $context['config']['skin_logo']['fidoma:*[dark]']);
    }

    public function test_it_rejects_invalid_hosts_and_missing_credentials(): void
    {
        Config::set('webmail.imap', [
            'host' => 'https://imap.example.test/path',
            'port' => 993,
            'scheme' => 'ssl',
            'username' => 'office@example.test',
            'password' => 'secret',
        ]);
        Config::set('mail.mailers.smtp', [
            'host' => 'smtp.example.test',
            'port' => 587,
            'scheme' => 'tls',
        ]);
        Config::set('mail.default', 'smtp');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('settings_invalid');

        app(RoundcubeConfiguration::class)->build();
    }

    public function test_archive_path_validation_rejects_traversal(): void
    {
        $this->assertFalse(RoundcubeInstaller::safe_archive_path('../evil.php'));
        $this->assertFalse(RoundcubeInstaller::safe_archive_path('a/../../b'));
        $this->assertFalse(RoundcubeInstaller::safe_archive_path('a\\b'));
        $this->assertTrue(RoundcubeInstaller::safe_archive_path('program/js/app.min.js'));
    }

    public function test_fidoma_skin_extends_elastic_and_contains_versioned_assets(): void
    {
        $metadata = json_decode((string) file_get_contents(resource_path('roundcube/skins/fidoma/meta.json')), true);
        $styles = (string) file_get_contents(resource_path('roundcube/skins/fidoma/styles/fidoma.css'));

        $this->assertSame('elastic', $metadata['extends']);
        $this->assertFileExists(resource_path('roundcube/skins/fidoma/styles/fidoma.css'));
        $this->assertFileExists(resource_path('roundcube/skins/fidoma/fidoma.js'));
        $this->assertStringContainsString(
            "preferences['mail.show.envelope'] = true",
            (string) file_get_contents(resource_path('roundcube/skins/fidoma/fidoma.js')),
        );
        $this->assertStringContainsString('#taskmenu a.compose > .inner', $styles);
        $this->assertStringContainsString('#taskmenu a.mail > .inner', $styles);
        $this->assertStringContainsString('#taskmenu a.contacts > .inner', $styles);
        $this->assertStringContainsString('#taskmenu a.settings > .inner', $styles);
        $this->assertStringContainsString(
            'images/fidoma-mark.png',
            (string) file_get_contents(resource_path('roundcube/skins/fidoma/watermark.html')),
        );
        $this->assertNotSame('', RoundcubeInstaller::SKIN_VERSION);
    }
}
