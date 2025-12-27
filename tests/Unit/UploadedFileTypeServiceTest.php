<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tiloweb\UploadedFileTypeBundle\Tests\App\TestFilesystem;
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeService;

final class UploadedFileTypeServiceTest extends TestCase
{
    private TestFilesystem $filesystem;
    private ContainerInterface $locator;
    private UploadedFileTypeService $service;

    protected function setUp(): void
    {
        $this->filesystem = new TestFilesystem();

        $this->locator = $this->createMock(ContainerInterface::class);
        $this->locator->method('has')
            ->willReturnCallback(static fn (string $id): bool => $id === 'test.filesystem');
        $this->locator->method('get')
            ->willReturnCallback(fn (string $id) => $id === 'test.filesystem' ? $this->filesystem : null);

        $this->service = new UploadedFileTypeService(
            [
                'default' => [
                    'filesystem' => 'test.filesystem',
                    'base_uri' => 'https://cdn.example.com',
                    'path' => '/uploads',
                ],
                'avatars' => [
                    'filesystem' => 'test.filesystem',
                    'base_uri' => 'https://cdn.example.com',
                    'path' => '/avatars',
                ],
                'no_path' => [
                    'filesystem' => 'test.filesystem',
                    'base_uri' => 'https://cdn.example.com',
                    'path' => null,
                ],
            ],
            $this->locator,
        );
    }

    public function test_has_configuration_returns_true_for_existing(): void
    {
        self::assertTrue($this->service->hasConfiguration('default'));
        self::assertTrue($this->service->hasConfiguration('avatars'));
    }

    public function test_has_configuration_returns_false_for_non_existing(): void
    {
        self::assertFalse($this->service->hasConfiguration('nonexistent'));
    }

    public function test_get_configuration_returns_correct_config(): void
    {
        $config = $this->service->getConfiguration('default');

        self::assertSame('test.filesystem', $config['filesystem']);
        self::assertSame('https://cdn.example.com', $config['base_uri']);
        self::assertSame('/uploads', $config['path']);
    }

    public function test_get_configuration_returns_first_when_not_found(): void
    {
        $config = $this->service->getConfiguration('nonexistent');

        self::assertSame('test.filesystem', $config['filesystem']);
    }

    public function test_get_configuration_names_returns_all_names(): void
    {
        $names = $this->service->getConfigurationNames();

        self::assertSame(['default', 'avatars', 'no_path'], $names);
    }

    public function test_upload_stores_file_and_returns_url(): void
    {
        $tempFile = $this->createTempFile('Hello, World!');
        $uploadedFile = new UploadedFile($tempFile, 'test.txt', 'text/plain', null, true);

        $url = $this->service->upload('myfile.txt', $uploadedFile, 'default');

        self::assertSame('https://cdn.example.com/uploads/myfile.txt', $url);
        self::assertTrue($this->filesystem->fileExists('uploads/myfile.txt'));
        self::assertSame('Hello, World!', $this->filesystem->read('uploads/myfile.txt'));

        unlink($tempFile);
    }

    public function test_upload_without_path_stores_at_root(): void
    {
        $tempFile = $this->createTempFile('content');
        $uploadedFile = new UploadedFile($tempFile, 'test.txt', 'text/plain', null, true);

        $url = $this->service->upload('file.txt', $uploadedFile, 'no_path');

        self::assertSame('https://cdn.example.com/file.txt', $url);
        self::assertTrue($this->filesystem->fileExists('file.txt'));

        unlink($tempFile);
    }

    public function test_upload_returns_null_for_invalid_configuration(): void
    {
        $tempFile = $this->createTempFile('content');
        $uploadedFile = new UploadedFile($tempFile, 'test.txt', 'text/plain', null, true);

        $url = $this->service->upload('file.txt', $uploadedFile, 'nonexistent');

        self::assertNull($url);

        unlink($tempFile);
    }

    public function test_upload_returns_null_for_null_configuration(): void
    {
        $tempFile = $this->createTempFile('content');
        $uploadedFile = new UploadedFile($tempFile, 'test.txt', 'text/plain', null, true);

        $url = $this->service->upload('file.txt', $uploadedFile, null);

        self::assertNull($url);

        unlink($tempFile);
    }

    public function test_delete_removes_file(): void
    {
        $this->filesystem->write('uploads/test.txt', 'content');

        $result = $this->service->delete('https://cdn.example.com/uploads/test.txt', 'default');

        self::assertTrue($result);
        self::assertFalse($this->filesystem->fileExists('uploads/test.txt'));
    }

    public function test_delete_returns_false_for_nonexistent_file(): void
    {
        $result = $this->service->delete('https://cdn.example.com/uploads/nonexistent.txt', 'default');

        self::assertFalse($result);
    }

    public function test_delete_returns_false_for_invalid_configuration(): void
    {
        $result = $this->service->delete('https://cdn.example.com/uploads/test.txt', 'nonexistent');

        self::assertFalse($result);
    }

    public function test_exists_returns_true_for_existing_file(): void
    {
        $this->filesystem->write('uploads/test.txt', 'content');

        $result = $this->service->exists('https://cdn.example.com/uploads/test.txt', 'default');

        self::assertTrue($result);
    }

    public function test_exists_returns_false_for_nonexistent_file(): void
    {
        $result = $this->service->exists('https://cdn.example.com/uploads/nonexistent.txt', 'default');

        self::assertFalse($result);
    }

    public function test_exists_returns_false_for_invalid_configuration(): void
    {
        $result = $this->service->exists('https://cdn.example.com/uploads/test.txt', 'nonexistent');

        self::assertFalse($result);
    }

    private function createTempFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        if ($tempFile === false) {
            throw new RuntimeException('Could not create temp file');
        }

        file_put_contents($tempFile, $content);

        return $tempFile;
    }
}
