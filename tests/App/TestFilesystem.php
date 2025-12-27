<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle\Tests\App;

use DateTimeInterface;
use Generator;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use RuntimeException;

use function sprintf;
use function strlen;

/**
 * In-memory filesystem for testing purposes.
 */
final class TestFilesystem implements FilesystemOperator
{
    /** @var array<string, string> */
    private array $files = [];

    public function fileExists(string $location): bool
    {
        return isset($this->files[$location]);
    }

    public function directoryExists(string $location): bool
    {
        foreach (array_keys($this->files) as $path) {
            if (str_starts_with($path, rtrim($location, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    public function has(string $location): bool
    {
        return $this->fileExists($location) || $this->directoryExists($location);
    }

    public function read(string $location): string
    {
        if (!$this->fileExists($location)) {
            throw new RuntimeException(sprintf('File not found: %s', $location));
        }

        return $this->files[$location];
    }

    public function readStream(string $location)
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Could not create memory stream');
        }

        fwrite($stream, $this->read($location));
        rewind($stream);

        return $stream;
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        $location = rtrim($location, '/');
        $items = [];

        foreach ($this->files as $path => $content) {
            if ($location !== '' && !str_starts_with($path, $location.'/')) {
                continue;
            }

            $relativePath = $location !== '' ? substr($path, strlen($location) + 1) : $path;

            if (!$deep && str_contains($relativePath, '/')) {
                continue;
            }

            $items[] = new FileAttributes($path);
        }

        return new DirectoryListing((static function () use ($items): Generator {
            yield from $items;
        })());
    }

    public function lastModified(string $path): int
    {
        return time();
    }

    public function fileSize(string $path): int
    {
        if (!$this->fileExists($path)) {
            throw new RuntimeException(sprintf('File not found: %s', $path));
        }

        return strlen($this->files[$path]);
    }

    public function mimeType(string $path): string
    {
        return 'application/octet-stream';
    }

    public function visibility(string $path): string
    {
        return 'public';
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->files[$location] = $contents;
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $this->files[$location] = stream_get_contents($contents);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // No-op for test filesystem
    }

    public function delete(string $location): void
    {
        unset($this->files[$location]);
    }

    public function deleteDirectory(string $location): void
    {
        $location = rtrim($location, '/');

        foreach (array_keys($this->files) as $path) {
            if (str_starts_with($path, $location.'/')) {
                unset($this->files[$path]);
            }
        }
    }

    public function createDirectory(string $location, array $config = []): void
    {
        // No-op for test filesystem
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->files[$destination] = $this->files[$source];
        unset($this->files[$source]);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $this->files[$destination] = $this->files[$source];
    }

    public function publicUrl(string $path, array $config = []): string
    {
        return 'https://cdn.example.com/'.$path;
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, array $config = []): string
    {
        return 'https://cdn.example.com/temp/'.$path;
    }

    public function checksum(string $path, array $config = []): string
    {
        return md5($this->read($path));
    }

    /**
     * Get all stored files (for testing assertions).
     *
     * @return array<string, string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Clear all stored files (for test cleanup).
     */
    public function clear(): void
    {
        $this->files = [];
    }
}
