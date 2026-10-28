<?php

use App\Features\Webmail\Services\RoundcubeInstaller;

if (! defined('FIDOMA_ROUNDCUBE_GATEWAY')) {
    http_response_code(403);
    exit;
}

/** Roundcube plugin; credentials exist only in the authenticated PHP request. */
class fidoma extends rcube_plugin
{
    public function init()
    {
        $this->add_hook('startup', [$this, 'startup']);
        $this->add_hook('authenticate', static function (array $args): array {
            $args['abort'] = true; // No switching away from the configured shared account.

            return $args;
        });
        $this->add_hook('user_create', [$this, 'identity']);
        $this->add_hook('write_log', static function (array $args): array {
            $context = $GLOBALS['fidoma_roundcube'];
            $secrets = array_filter([$context['password'], $context['config']['smtp_pass']]);
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
    }

    public function startup(array $args): array
    {
        $context = $GLOBALS['fidoma_roundcube'];
        $rcmail = rcmail::get_instance();
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
}
