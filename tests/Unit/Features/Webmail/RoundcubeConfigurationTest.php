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
}
