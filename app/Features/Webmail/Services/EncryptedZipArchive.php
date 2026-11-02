<?php

namespace App\Features\Webmail\Services;

use RuntimeException;
use ZipArchive;

final class EncryptedZipArchive
{
    private const MAX_ENTRIES = 100;

    private const MAX_ENTRY_BYTES = 25 * 1024 * 1024;

    private const MAX_TOTAL_BYTES = 100 * 1024 * 1024;

    private const MAX_COMPRESSION_RATIO = 200;

    public function createUnprotectedCopy(string $source, string $destination, string $password): void
    {
        $archive = new ZipArchive;

        if ($archive->open($source) !== true || ! $archive->setPassword($password)) {
            throw new RuntimeException('zip_invalid');
        }

        $output = new ZipArchive;

        if ($output->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $archive->close();

            throw new RuntimeException('zip_failed');
        }

        $totalBytes = 0;
        $names = [];

        $completed = false;
        $outputOpen = true;

        try {
            if ($archive->numFiles < 1 || $archive->numFiles > self::MAX_ENTRIES) {
                throw new RuntimeException('zip_invalid');
            }

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);

                if (! is_array($entry) || ! isset($entry['name'], $entry['size'], $entry['comp_size'])) {
                    throw new RuntimeException('zip_invalid');
                }

                $name = (string) $entry['name'];
                $size = (int) $entry['size'];
                $compressedSize = (int) $entry['comp_size'];

                if (! self::isSafeEntryName($name) || isset($names[$name]) || $this->isSymbolicLink($archive, $index)) {
                    throw new RuntimeException('zip_invalid');
                }

                $names[$name] = true;

                if (str_ends_with($name, '/')) {
                    if (! $output->addEmptyDir(rtrim($name, '/'))) {
                        throw new RuntimeException('zip_failed');
                    }

                    continue;
                }

                $totalBytes += $size;

                if ($size < 0 || $size > self::MAX_ENTRY_BYTES || $totalBytes > self::MAX_TOTAL_BYTES
                    || ($size > 0 && $size / max(1, $compressedSize) > self::MAX_COMPRESSION_RATIO)) {
                    throw new RuntimeException('zip_invalid');
                }

                $contents = $archive->getFromIndex($index, self::MAX_ENTRY_BYTES + 1);

                if (! is_string($contents) || strlen($contents) !== $size || ! $output->addFromString($name, $contents)) {
                    throw new RuntimeException('zip_password_failed');
                }
            }

            $outputClosed = $output->close();
            $outputOpen = false;

            if (! $outputClosed) {
                throw new RuntimeException('zip_failed');
            }

            $completed = true;
        } finally {
            $archive->close();

            if ($outputOpen) {
                $output->close();
            }

            if (! $completed && is_file($destination)) {
                unlink($destination);
            }
        }
    }

    public static function isSafeEntryName(string $name): bool
    {
        if ($name === '' || str_contains($name, "\0") || str_contains($name, '\\')
            || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name)) {
            return false;
        }

        foreach (explode('/', rtrim($name, '/')) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function isSymbolicLink(ZipArchive $archive, int $index): bool
    {
        $operatingSystem = 0;
        $attributes = 0;

        if (! $archive->getExternalAttributesIndex($index, $operatingSystem, $attributes)) {
            return false;
        }

        return (($attributes >> 16) & 0170000) === 0120000;
    }
}
