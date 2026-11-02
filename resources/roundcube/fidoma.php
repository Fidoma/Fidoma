<?php

use App\Features\Webmail\Services\EncryptedZipArchive;
use App\Features\Webmail\Services\RoundcubeInstaller;

if (! defined('FIDOMA_ROUNDCUBE_GATEWAY')) {
    http_response_code(403);
    exit;
}

/** Roundcube plugin; credentials exist only in the authenticated PHP request. */
class fidoma extends rcube_plugin
{
    private const MAX_ARCHIVE_BYTES = 25 * 1024 * 1024;

    public function init()
    {
        $rcmail = rcmail::get_instance();

        $this->add_hook('startup', [$this, 'startup']);
        $this->add_hook('authenticate', static function (array $args): array {
            $args['abort'] = true; // No switching away from the configured shared account.

            return $args;
        });
        $this->add_hook('user_create', [$this, 'identity']);
        $this->add_hook('write_log', static function (array $args): array {
            $context = $GLOBALS['fidoma_roundcube'];
            $secrets = array_filter([
                $context['password'],
                $context['config']['smtp_pass'],
                $context['archive_rule']['password'] ?? null,
            ], fn ($secret): bool => is_string($secret) && $secret !== '');
            $line = str_replace($secrets, '[redacted]', (string) $args['line']);
            $args['line'] = preg_replace('/[\r\n]+/', '', $line) ?? '';

            return $args;
        });
        // The mailbox is opened automatically for the Fidoma administrator.
        // Remove Roundcube's logout control so the iframe cannot navigate away
        // to the logout redirect. Direct logout requests remain handled below.
        $this->add_hook('template_object_logout', static function (array $args): array {
            $args['content'] = '';

            return $args;
        });
        // Elastic renders the menu item directly from its template, so hide
        // that rendered control as a final presentation safeguard as well.
        $this->add_hook('render_page', static function (array $args): array {
            $assets = rtrim((string) $GLOBALS['fidoma_roundcube']['config']['assets_path'], '/');
            $skin = htmlspecialchars($assets.'/skins/fidoma', ENT_QUOTES, 'UTF-8');
            $head = '<link rel="stylesheet" href="'.$skin.'/styles/fidoma.css?v='.
                rawurlencode(RoundcubeInstaller::SKIN_VERSION).'">'
                .'<script defer src="'.$skin.'/fidoma.js?v='.
                rawurlencode(RoundcubeInstaller::SKIN_VERSION).'"></script>';
            $args['content'] = str_replace('</head>', $head.'</head>', (string) $args['content']);

            return $args;
        });
        $this->add_hook('logout_after', static function (): void {
            header('Location: '.$GLOBALS['fidoma_roundcube']['return_url']);
            exit;
        });

        $this->register_action('plugin.fidoma.decryptzip', [$this, 'downloadDecryptedZip']);

        if (! empty($GLOBALS['fidoma_roundcube']['archive_rule'])
            && in_array($rcmail->action, ['show', 'preview'], true)) {
            $this->add_hook('template_object_messageattachments', [$this, 'rewriteZipAttachments']);
        }
    }

    public function startup(array $args): array
    {
        $context = $GLOBALS['fidoma_roundcube'];
        $rcmail = rcmail::get_instance();
        $rcmail->output->set_env('fidoma_ai_templates', $context['ai_templates'] ?? []);
        $rcmail->output->set_env('fidoma_ai_actions_label', $context['ai_actions_label'] ?? 'KI-Aktionen');

        if (! empty($_SESSION['user_id'])
            && hash_equals($context['binding'], (string) ($_SESSION['fidoma_binding'] ?? ''))) {
            return $args;
        }

        $rcmail->kill_session();
        // Never replay an old POST/action after a Fidoma login or account change.
        if ($context['request_method'] !== 'GET' || ! empty($args['action'])) {
            http_response_code(403);
            exit;
        }
        if (! $rcmail->login($context['username'], $context['password'], $context['config']['imap_host'], false)) {
            http_response_code(502);
            echo htmlspecialchars($context['failure_message'], ENT_QUOTES, 'UTF-8');
            exit;
        }
        $_SESSION['fidoma_binding'] = $context['binding'];
        $rcmail->session->remove('temp');
        $rcmail->session->regenerate_id(false);
        $rcmail->session->set_auth_cookie();
        $rcmail->output->redirect(['_task' => 'mail', '_mbox' => $context['mailbox']], 0, true);

        return ['task' => 'mail', 'action' => ''];
    }

    public function identity(array $args): array
    {
        $email = $GLOBALS['fidoma_roundcube']['email'];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $args['user_email'] = $email;
            $args['email_list'] = null;
        }

        return $args;
    }

    public function rewriteZipAttachments(array $args): array
    {
        $rcmail = rcmail::get_instance();
        $rule = $GLOBALS['fidoma_roundcube']['archive_rule'] ?? null;
        $uid = (string) $rcmail->output->get_env('uid');
        $mailbox = (string) $rcmail->output->get_env('mailbox');

        if (! is_array($rule) || $uid === '' || $mailbox === '') {
            return $args;
        }

        $message = new rcube_message($uid, $mailbox);

        if (! $this->messageMatchesRule($message, $rule)) {
            return $args;
        }

        foreach ($message->attachments as $attachment) {
            if (! $this->isZipAttachment($attachment)) {
                continue;
            }

            $url = $rcmail->url([
                '_action' => 'plugin.fidoma.decryptzip',
                '_mbox' => $mailbox,
                '_uid' => $uid,
                '_part' => $attachment->mime_id,
            ], false, false, true);

            $args['content'] = $this->replaceAttachmentUrl(
                (string) $args['content'],
                'attach'.$attachment->mime_id,
                $url,
            );
        }

        return $args;
    }

    public function downloadDecryptedZip(): void
    {
        $rcmail = rcmail::get_instance();
        $rcmail->request_security_check(rcube_utils::INPUT_GET);

        $rule = $GLOBALS['fidoma_roundcube']['archive_rule'] ?? null;
        $uid = rcube_utils::get_input_string('_uid', rcube_utils::INPUT_GET);
        $mailbox = rcube_utils::get_input_string('_mbox', rcube_utils::INPUT_GET);
        $partId = rcube_utils::get_input_string('_part', rcube_utils::INPUT_GET);

        if (! is_array($rule) || ! preg_match('/^\d+(?:\.\d+)*$/', $uid)
            || $mailbox === '' || strlen($mailbox) > 1024
            || ! preg_match('/^\d+(?:\.\d+)*$/', $partId)) {
            $this->failArchiveDownload(403, 'not_allowed');
        }

        $message = new rcube_message($uid, $mailbox);

        if (! $this->messageMatchesRule($message, $rule)) {
            $this->failArchiveDownload(403, 'not_allowed');
        }

        $attachment = null;

        foreach ($message->attachments as $candidate) {
            if ((string) $candidate->mime_id === $partId && $this->isZipAttachment($candidate)) {
                $attachment = $candidate;

                break;
            }
        }

        if ($attachment === null || (int) $attachment->size > self::MAX_ARCHIVE_BYTES) {
            $this->failArchiveDownload(422, 'failed');
        }

        $source = rcube_utils::temp_filename('fidoma-encrypted-zip');
        $destination = rcube_utils::temp_filename('fidoma-unprotected-zip');
        $stream = fopen($source, 'wb');

        if (! is_resource($stream)) {
            $this->removeTemporaryFiles([$source, $destination]);
            $this->failArchiveDownload(422, 'failed');
        }

        $downloadReady = false;

        try {
            $written = $message->get_part_body($partId, false, self::MAX_ARCHIVE_BYTES + 1, $stream);
            fclose($stream);
            $stream = null;

            if (! $written || ! is_file($source) || filesize($source) < 1
                || filesize($source) > self::MAX_ARCHIVE_BYTES) {
                throw new RuntimeException('zip_invalid');
            }

            app(EncryptedZipArchive::class)->createUnprotectedCopy(
                $source,
                $destination,
                (string) $rule['password'],
            );
            $downloadReady = true;
        } catch (Throwable) {
            $downloadReady = false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $downloadReady || ! is_file($destination)) {
            $this->removeTemporaryFiles([$source, $destination]);
            $this->failArchiveDownload(422, 'failed');
        }

        $filename = pathinfo((string) $attachment->filename, PATHINFO_FILENAME);
        $filename = trim(preg_replace('/[\x00-\x1F\x7F]+/', '', $filename) ?? '', ' .');
        $filename = ($filename !== '' ? $filename : 'Anhang').'-entschluesselt.zip';

        $rcmail->output->download_headers($filename, [
            'type' => 'application/zip',
            'length' => filesize($destination),
        ]);
        readfile($destination);
        $this->removeTemporaryFiles([$source, $destination]);
        exit;
    }

    /** @param array{sender?: mixed, password?: mixed} $rule */
    private function messageMatchesRule(rcube_message $message, array $rule): bool
    {
        $pattern = is_string($rule['sender'] ?? null) ? strtolower($rule['sender']) : '';
        $password = $rule['password'] ?? null;

        if ($message->headers === null || $pattern === '' || ! is_string($password) || $password === '') {
            return false;
        }

        $senders = rcube_mime::decode_address_list(
            (string) $message->headers->from,
            null,
            true,
            $message->headers->charset,
            true,
        );

        foreach ($senders as $sender) {
            $sender = strtolower(trim((string) $sender));

            if (str_starts_with($pattern, '*@')) {
                $domain = substr(strrchr($sender, '@') ?: '', 1);

                if ($domain !== '' && hash_equals(substr($pattern, 2), $domain)) {
                    return true;
                }
            } elseif (filter_var($sender, FILTER_VALIDATE_EMAIL) && hash_equals($pattern, $sender)) {
                return true;
            }
        }

        return false;
    }

    private function isZipAttachment(object $attachment): bool
    {
        $filename = strtolower((string) ($attachment->filename ?? ''));

        return str_ends_with($filename, '.zip');
    }

    private function replaceAttachmentUrl(string $html, string $elementId, string $url): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $wrapperId = 'fidoma-attachment-root';

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8"><div id="'.$wrapperId.'">'.$html.'</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
            );

            if (! $loaded) {
                return $html;
            }

            $xpath = new DOMXPath($document);
            $links = $xpath->query(
                '//*[@id="'.$elementId.'"]//a[contains(concat(" ", normalize-space(@class), " "), " filename ")]',
            );

            if ($links === false || $links->length !== 1) {
                return $html;
            }

            $links->item(0)->setAttribute('href', $url);
            $links->item(0)->removeAttribute('onclick');
            $wrapper = $xpath->query('//*[@id="'.$wrapperId.'"]')->item(0);

            if ($wrapper === null) {
                return $html;
            }

            $result = '';

            foreach ($wrapper->childNodes as $child) {
                $result .= $document->saveHTML($child);
            }

            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function failArchiveDownload(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        $messages = $GLOBALS['fidoma_roundcube']['archive_messages'] ?? [];
        echo htmlspecialchars((string) ($messages[$message] ?? 'ZIP download failed.'), ENT_QUOTES, 'UTF-8');
        exit;
    }

    /** @param list<string> $files */
    private function removeTemporaryFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
