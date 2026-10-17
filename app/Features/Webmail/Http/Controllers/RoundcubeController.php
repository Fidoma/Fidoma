<?php

namespace App\Features\Webmail\Http\Controllers;

use App\Features\Webmail\Services\RoundcubeConfiguration;
use App\Features\Webmail\Services\RoundcubeInstaller;
use Illuminate\Http\Request;
use RuntimeException;
use SessionHandler;

class RoundcubeController
{
    public function __invoke(
        Request $request,
        RoundcubeInstaller $installer,
        RoundcubeConfiguration $configuration,
    ): never {
        abort_unless($installer->installed(), 503, __('webmail::messages.not_installed'));

        try {
            $context = $configuration->build();
        } catch (RuntimeException $exception) {
            abort(503, __('webmail::messages.'.$exception->getMessage()));
        }

        $binding = $request->session()->get('webmail_binding');

        if (! is_string($binding) || strlen($binding) !== 64) {
            $binding = bin2hex(random_bytes(32));
            $request->session()->put('webmail_binding', $binding);
        }

        $context['binding'] = hash_hmac('sha256', serialize($context), $binding);
        $context['request_method'] = $request->method();
        $context['failure_message'] = __('webmail::messages.login_failed');
        $context['return_url'] = route('filament.admin.pages.webmail');
        $context['config']['request_path'] = parse_url(route('webmail.client'), PHP_URL_PATH);
        $context['config']['assets_path'] = asset('roundcube/').'/';
        $context['config']['session_path'] = parse_url(config('app.url'), PHP_URL_PATH) ?: '/';
        $GLOBALS['fidoma_roundcube'] = $context;

        $request->session()->save();
        session_write_close();
        session_id('');
        session_set_save_handler(new SessionHandler, true);
        $_SESSION = [];

        header('Cache-Control: private, no-store');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        define('FIDOMA_ROUNDCUBE_GATEWAY', true);
        $_SERVER['PATH_INFO'] = '';

        $entry = $installer->private_path().'/release/public_html/index.php';

        (static function (string $entry): void {
            global $RCMAIL;

            require_once dirname($entry, 2).'/program/include/iniset.php';
            require_once resource_path('roundcube/session.php');
            require $entry;
        })($entry);

        exit;
    }
}
