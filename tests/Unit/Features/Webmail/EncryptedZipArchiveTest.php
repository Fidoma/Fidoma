<?php

namespace Tests\Unit\Features\Webmail;

use App\Features\Webmail\Services\EncryptedZipArchive;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class EncryptedZipArchiveTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/fidoma-zip-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function test_it_creates_an_unprotected_copy_of_an_encrypted_archive(): void
    {
        $source = $this->createEncryptedArchive('correct-password');
        $destination = $this->directory.'/unprotected.zip';

        app(EncryptedZipArchive::class)->createUnprotectedCopy($source, $destination, 'correct-password');

        $archive = new ZipArchive;

        $this->assertTrue($archive->open($destination));
        $this->assertSame('Invoice content', $archive->getFromName('documents/invoice.txt'));
        $archive->close();
    }

    public function test_it_rejects_an_incorrect_password_without_leaving_an_archive(): void
    {
        $destination = $this->directory.'/unprotected.zip';

        try {
            app(EncryptedZipArchive::class)->createUnprotectedCopy(
                $this->createEncryptedArchive('correct-password'),
                $destination,
                'incorrect-password',
            );

            $this->fail('An incorrect password should not create an archive.');
        } catch (RuntimeException $exception) {
            $this->assertSame('zip_password_failed', $exception->getMessage());
            $this->assertFileDoesNotExist($destination);
        }
    }

    public function test_it_rejects_unsafe_archive_paths(): void
    {
        $this->assertTrue(EncryptedZipArchive::isSafeEntryName('documents/invoice.txt'));
        $this->assertFalse(EncryptedZipArchive::isSafeEntryName('../invoice.txt'));
        $this->assertFalse(EncryptedZipArchive::isSafeEntryName('/invoice.txt'));
        $this->assertFalse(EncryptedZipArchive::isSafeEntryName('documents\\invoice.txt'));
        $this->assertFalse(EncryptedZipArchive::isSafeEntryName('C:/invoice.txt'));
    }

    private function createEncryptedArchive(string $password): string
    {
        $path = $this->directory.'/encrypted.zip';
        $archive = new ZipArchive;

        $this->assertTrue($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $this->assertTrue($archive->setPassword($password));
        $this->assertTrue($archive->addFromString('documents/invoice.txt', 'Invoice content'));
        $this->assertTrue($archive->setEncryptionName('documents/invoice.txt', ZipArchive::EM_AES_256));
        $this->assertTrue($archive->close());

        return $path;
    }
}
