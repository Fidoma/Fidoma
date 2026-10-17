<?php

namespace App\Features\Webmail\Services;

use FilesystemIterator;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/** Installs only the reviewed official complete release, without invoking a shell. */
class RoundcubeInstaller
{
    public const VERSION = '1.7.4';

    public const SHA256 = '2c6c878f0093f1bf7fb6086781d2dd9269d652c016b86939c157c5f1729139a2';

    private const MAX_DOWNLOAD = 16777216;

    private const ASSET_EXTENSIONS = ['avif', 'css', 'gif', 'jpg', 'jpeg', 'html', 'ico', 'js', 'json', 'less', 'mp3', 'png', 'svg', 'wav', 'webp', 'woff', 'woff2', 'ttf', 'eot'];

    public function private_path(): string
    {
        $configuredPath = trim((string) config('webmail.private_dir'));

        return rtrim($configuredPath !== '' ? $configuredPath : storage_path('app/private/roundcube'), '/\\');
    }

    public function installed(): bool
    {
        $release = $this->private_path().'/release';

        return is_file($release.'/fidoma-installed.json')
            && is_file($release.'/public_html/index.php')
            && is_file($release.'/config/config.inc.php')
            && is_dir(public_path().'/'.'roundcube/skins/elastic');
    }

    public function install(): void
    {
        if ($this->installed()) {
            return;
        }
        if (PHP_VERSION_ID < 80200) {
            throw new RuntimeException('webmail_requirements');
        }
        foreach (['curl', 'zlib', 'pdo_sqlite', 'dom', 'xml', 'intl', 'mbstring', 'openssl', 'fileinfo', 'iconv'] as $extension) {
            if (! extension_loaded($extension)) {
                throw new RuntimeException('webmail_requirements');
            }
        }
        $private = $this->private_path();
        $this->prepare_private_directory($private);
        $lock = fopen($private.'/install.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('webmail_directory_failed');
        }
        $stage = $private.'/stage-'.bin2hex(random_bytes(12));
        $assets = public_path().'/'.'.roundcube-assets-'.bin2hex(random_bytes(12));
        $published_assets = false;
        try {
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('webmail_install_busy');
            }
            if ($this->installed()) {
                return;
            }
            // Never overwrite an existing Roundcube installation or an unrelated folder.
            $public_directory = public_path().'/'.'roundcube';
            $reuse_public = $this->has_public_assets($public_directory);
            if (file_exists($private.'/release') || is_link($private.'/release')
                || is_link($public_directory)
                || (file_exists($public_directory) && ! $reuse_public && ! $this->is_empty_directory($public_directory))) {
                throw new RuntimeException('webmail_directory_exists');
            }
            // The runtime bootstrap may create the target directory in advance.
            // Reuse it only when it is genuinely empty.
            if (! $reuse_public && is_dir($public_directory) && ! rmdir($public_directory)) {
                throw new RuntimeException('webmail_directory_failed');
            }
            $this->mkdir($stage, 0700);
            $archive = $stage.'/roundcube.tar.gz';
            $this->download($archive);
            if (! hash_equals(self::SHA256, hash_file('sha256', $archive))) {
                throw new RuntimeException('webmail_checksum_failed');
            }
            $package = $stage.'/package';
            $this->mkdir($package, 0700);
            $this->extract($archive, $package);
            $this->configure($package, $private.'/release');
            if (! $reuse_public) {
                $this->publish_assets($package, $assets);
            }
            $this->write($package.'/fidoma-installed.json', json_encode(['version' => self::VERSION, 'sha256' => self::SHA256]));
            if (! $reuse_public && ! rename($assets, public_path().'/'.'roundcube')) {
                throw new RuntimeException('webmail_directory_failed');
            }
            $published_assets = ! $reuse_public;
            if (! rename($package, $private.'/release')) {
                throw new RuntimeException('webmail_directory_failed');
            }
            $published_assets = false;
        } finally {
            if ($published_assets) {
                $this->remove_tree(public_path().'/'.'roundcube');
            }
            $this->remove_tree($assets);
            $this->remove_tree($stage);
            fclose($lock);
        }
    }

    private function prepare_private_directory(string $path): void
    {
        $parent = realpath(dirname($path));
        if ($parent === false || is_link($path) || ! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('webmail_private_directory');
        }
        $resolved = $parent.'/'.basename($path);
        foreach ([public_path().'/', $_SERVER['DOCUMENT_ROOT'] ?? public_path().'/'] as $root) {
            $root = realpath($root);
            if ($root !== false && ($resolved === $root || str_starts_with($resolved, rtrim($root, '/').'/'))) {
                throw new RuntimeException('webmail_private_directory');
            }
        }
        $this->mkdir($path, 0700);
        if (! is_writable($path) || ! is_writable(public_path().'/')) {
            throw new RuntimeException('webmail_directory_failed');
        }
    }

    private function download(string $target): void
    {
        $file = fopen($target, 'xb');
        if ($file === false) {
            throw new RuntimeException('webmail_directory_failed');
        }
        $curl = curl_init('https://github.com/roundcube/roundcubemail/releases/download/'.self::VERSION
            .'/roundcubemail-'.self::VERSION.'-complete.tar.gz');
        $bytes = 0;
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Fidoma Roundcube Installer',
            CURLOPT_WRITEFUNCTION => static function ($handle, string $data) use ($file, &$bytes): int {
                $bytes += strlen($data);

                return $bytes <= self::MAX_DOWNLOAD ? (int) fwrite($file, $data) : 0;
            },
        ]);
        try {
            $success = curl_exec($curl);
            if ($success === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                throw new RuntimeException('webmail_download_failed');
            }
        } finally {
            curl_close($curl);
            fclose($file);
        }
    }

    private function extract(string $archive, string $destination): void
    {
        $stream = gzopen($archive, 'rb');
        if ($stream === false) {
            throw new RuntimeException('webmail_archive_invalid');
        }
        $prefix = 'roundcubemail-'.self::VERSION.'/';
        $bytes = 0;
        $count = 0;
        $long_name = null;
        try {
            while (true) {
                $header = $this->read_archive_bytes($stream, 512);
                if ($header === str_repeat("\0", 512)) {
                    if ($long_name !== null || $this->read_archive_bytes($stream, 512) !== $header) {
                        throw new RuntimeException('webmail_archive_invalid');
                    }
                    break;
                }
                $size_field = trim(substr($header, 124, 12), " \0");
                $checksum = trim(substr($header, 148, 8), " \0");
                $sum = array_sum(unpack('C*', substr_replace($header, str_repeat(' ', 8), 148, 8)));
                if (! preg_match('/^[0-7]+$/D', $size_field) || ! preg_match('/^[0-7]+$/D', $checksum)
                    || octdec($checksum) !== $sum || ++$count > 20000) {
                    throw new RuntimeException('webmail_archive_invalid');
                }
                $size = (int) octdec($size_field);
                if ($size > self::MAX_DOWNLOAD || ($bytes += $size) > 134217728) {
                    throw new RuntimeException('webmail_archive_invalid');
                }
                $type = $header[156];
                $content = $this->read_archive_bytes($stream, $size);
                $this->read_archive_bytes($stream, (512 - $size % 512) % 512);
                // The official GNU tar archive uses long-name headers. PharData cannot
                // reliably extract these entries on supported PHP versions.
                if ($type === 'L' && $long_name === null && $size <= 4096) {
                    $long_name = rtrim($content, "\0");

                    continue;
                }
                $name = $long_name ?? rtrim(substr($header, 0, 100), "\0");
                $long_name = null;
                if (! in_array($type, ['0', "\0", '5'], true) || ! str_starts_with($name, $prefix)) {
                    throw new RuntimeException('webmail_archive_invalid');
                }
                $relative = rtrim(substr($name, strlen($prefix)), '/');
                if ($relative === '' && $type === '5') {
                    continue;
                }
                if (! self::safe_archive_path($relative)) {
                    throw new RuntimeException('webmail_archive_invalid');
                }
                $target = $destination.'/'.$relative;
                if ($type === '5') {
                    $this->mkdir($target, 0700);
                } else {
                    $this->mkdir(dirname($target), 0700);
                    $this->write($target, $content);
                }
            }
        } finally {
            gzclose($stream);
        }
        foreach (['public_html/index.php', 'vendor/autoload.php', 'SQL/sqlite.initial.sql', 'config/defaults.inc.php'] as $file) {
            if (! is_file($destination.'/'.$file)) {
                throw new RuntimeException('webmail_archive_invalid');
            }
        }
    }

    private function read_archive_bytes($stream, int $length): string
    {
        $result = '';
        while (strlen($result) < $length) {
            $part = gzread($stream, $length - strlen($result));
            if ($part === false || $part === '') {
                throw new RuntimeException('webmail_archive_invalid');
            }
            $result .= $part;
        }

        return $result;
    }

    private function is_empty_directory(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }
        $entries = scandir($path);

        return $entries !== false && count($entries) === 2;
    }

    private function has_public_assets(string $path): bool
    {
        return is_dir($path.'/plugins')
            && is_dir($path.'/program')
            && is_dir($path.'/skins');
    }

    public static function safe_archive_path(string $path): bool
    {
        if ($path === '' || str_contains($path, '\\') || preg_match('/[\x00-\x1f\x7f:]/', $path)) {
            return false;
        }
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                return false;
            }
        }

        return true;
    }

    private function configure(string $package, string $final): void
    {
        foreach (['data', 'logs', 'temp', 'plugins/fidoma'] as $dir) {
            $this->mkdir($package.'/'.$dir, 0700);
        }
        $database = new PDO('sqlite:'.$package.'/data/roundcube.sqlite');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->exec(file_get_contents($package.'/SQL/sqlite.initial.sql'));
        $database = null;
        chmod($package.'/data/roundcube.sqlite', 0600);
        $config = [
            'db_dsnw' => 'sqlite:///'.$final.'/data/roundcube.sqlite?mode=0640',
            'des_key' => bin2hex(random_bytes(12)),
            'temp_dir' => $final.'/temp/',
            'log_dir' => $final.'/logs/',
        ];
        $this->write($package.'/config/config.inc.php', "<?php\n"
            ."if (!defined('FIDOMA_ROUNDCUBE_GATEWAY')) { http_response_code(403); exit; }\n"
            .'$config = array_replace('.var_export($config, true).", \$GLOBALS['fidoma_roundcube']['config']);\n");
        $this->write($package.'/plugins/fidoma/fidoma.php', "<?php\nrequire "
            .var_export(resource_path('roundcube/fidoma.php'), true).";\n");
    }

    private function publish_assets(string $package, string $destination): void
    {
        $this->mkdir($destination, 0755);
        // Only immutable static assets from the hash-verified release enter the web root.
        foreach (['skins', 'program/js', 'program/resources', 'plugins'] as $directory) {
            if (! is_dir($package.'/'.$directory)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($package.'/'.$directory, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $relative = substr($file->getPathname(), strlen($package) + 1);
                if (! $file->isFile() || $file->isLink()
                    || ! in_array(strtolower($file->getExtension()), self::ASSET_EXTENSIONS, true)
                    || str_contains($relative, '/templates/') || str_contains($relative, '/config')
                    || preg_match('~/(composer|package|meta)\.json$~', $relative)) {
                    continue;
                }
                $target = $destination.'/'.$relative;
                $this->mkdir(dirname($target), 0755);
                $this->write($target, file_get_contents($file->getPathname()), 0644);
            }
        }
    }

    private function mkdir(string $path, int $mode): void
    {
        if (! is_dir($path) && ! mkdir($path, $mode, true) && ! is_dir($path)) {
            throw new RuntimeException('webmail_directory_failed');
        }
    }

    private function write(string $path, string $content, int $mode = 0600): void
    {
        if (file_put_contents($path, $content, LOCK_EX) !== strlen($content) || ! chmod($path, $mode)) {
            throw new RuntimeException('webmail_directory_failed');
        }
    }

    private function remove_tree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $file) {
                $this->remove_tree($file->getPathname());
            }
            rmdir($path);
        }
    }
}
