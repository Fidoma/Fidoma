<?php

namespace App\Features\Webmail\Services;

use RuntimeException;

class RoundcubeConfiguration
{
    /** @return array{config: array<string, mixed>, username: string, password: string, mailbox: string, email: string} */
    public function build(): array
    {
        $username = trim((string) config('webmail.imap.username'));
        $password = (string) config('webmail.imap.password');

        if ($username === '' || $password === '') {
            throw new RuntimeException('settings_required');
        }

        $config = [
            'imap_host' => $this->endpoint(
                config('webmail.imap.host'),
                config('webmail.imap.port'),
                config('webmail.imap.scheme'),
            ),
            'smtp_host' => $this->endpoint(
                config('mail.mailers.smtp.host'),
                config('mail.mailers.smtp.port'),
                config('mail.mailers.smtp.scheme'),
            ),
            'smtp_user' => (string) config('mail.mailers.smtp.username'),
            'smtp_pass' => (string) config('mail.mailers.smtp.password'),
            'imap_conn_options' => $this->tlsOptions((bool) config('webmail.imap.verify_certificates', true)),
            'smtp_conn_options' => $this->tlsOptions((bool) config('webmail.smtp_verify_certificates', true)),
            'imap_timeout' => 20,
            'smtp_timeout' => 20,
            'draft_autosave' => 60,
            'product_name' => 'Fidoma Webmail',
            'skin' => 'elastic',
            'plugins' => ['fidoma'],
            'enable_installer' => false,
            'x_frame_options' => 'sameorigin',
            'session_name' => 'fidoma_roundcube',
            'session_auth_name' => 'fidoma_roundcube_auth',
            'session_samesite' => 'Lax',
            'session_storage' => 'fidoma',
            'log_driver' => 'file',
            'log_session_id' => 0,
            'smtp_log' => false,
            'imap_debug' => false,
            'smtp_debug' => false,
            'session_debug' => false,
            'login_autocomplete' => 0,
            'identities_level' => 3,
        ];
        $config['dont_override'] = array_keys($config);

        return [
            'config' => $config,
            'username' => $username,
            'password' => $password,
            'mailbox' => (string) config('webmail.imap.mailbox', 'INBOX'),
            'email' => (string) config('mail.from.address'),
        ];
    }

    private function endpoint(mixed $host, mixed $port, mixed $scheme): string
    {
        $host = trim((string) $host);
        $port = filter_var($port, FILTER_VALIDATE_INT);
        $scheme = strtolower(trim((string) $scheme));

        if ($host === '' || (! filter_var($host, FILTER_VALIDATE_IP)
                && ! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
            || $port === false || $port < 1 || $port > 65535
            || ! in_array($scheme, ['', 'ssl', 'tls'], true)) {
            throw new RuntimeException('settings_invalid');
        }

        if (str_contains($host, ':')) {
            $host = '['.$host.']';
        }

        return ($scheme !== '' ? $scheme.'://' : '').$host.':'.$port;
    }

    /** @return array{ssl: array{verify_peer: bool, verify_peer_name: bool, allow_self_signed: bool}} */
    private function tlsOptions(bool $verify): array
    {
        return ['ssl' => [
            'verify_peer' => $verify,
            'verify_peer_name' => $verify,
            'allow_self_signed' => ! $verify,
        ]];
    }
}
