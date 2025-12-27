<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle;

use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function is_resource;
use function sprintf;
use function strlen;

/**
 * Service responsible for handling file uploads to configured filesystems.
 *
 * @author Thibault HENRY <thibault@henry.pro>
 */
final class UploadedFileTypeService
{
    /**
     * @param array<string, array{filesystem: string, base_uri: string|null, path: string|null}> $configurations
     */
    public function __construct(
        private readonly array $configurations,
        private readonly ContainerInterface $locator,
    ) {
    }

    /**
     * Check if a configuration exists.
     */
    public function hasConfiguration(string $configuration): bool
    {
        return isset($this->configurations[$configuration]);
    }

    /**
     * Get a specific configuration or the first one if not found.
     *
     * @throws InvalidArgumentException When no configuration exists
     * @return array{filesystem: string, base_uri: string|null, path: string|null}
     */
    public function getConfiguration(string $configuration = 'default'): array
    {
        if ($this->hasConfiguration($configuration)) {
            return $this->configurations[$configuration];
        }

        if ($this->configurations === []) {
            throw new InvalidArgumentException('No upload configuration found. Please configure at least one filesystem.');
        }

        // Get the first configuration without modifying the array
        $keys = array_keys($this->configurations);

        return $this->configurations[$keys[0]];
    }

    /**
     * Get all available configuration names.
     *
     * @return array<string>
     */
    public function getConfigurationNames(): array
    {
        return array_keys($this->configurations);
    }

    /**
     * Upload a file to the configured filesystem.
     *
     * @throws FilesystemException When the upload fails
     */
    public function upload(string $filename, UploadedFile $uploadedFile, ?string $configuration): ?string
    {
        if ($configuration === null || !$this->hasConfiguration($configuration)) {
            return null;
        }

        $config = $this->configurations[$configuration];
        $path = ltrim($config['path'] ?? '', '/');
        $filePath = $path !== '' ? $path.'/'.$filename : $filename;

        $filesystem = $this->getFilesystem($config['filesystem']);

        $stream = fopen($uploadedFile->getRealPath(), 'r');
        if ($stream === false) {
            throw new RuntimeException(sprintf('Could not open file "%s" for reading.', $uploadedFile->getRealPath()));
        }

        try {
            $filesystem->writeStream($filePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $baseUri = rtrim($config['base_uri'] ?? '', '/');

        return $baseUri !== '' ? $baseUri.'/'.$filePath : '/'.$filePath;
    }

    /**
     * Delete a file from the configured filesystem.
     *
     * @throws FilesystemException When the deletion fails
     */
    public function delete(string $url, ?string $configuration): bool
    {
        if ($configuration === null || !$this->hasConfiguration($configuration)) {
            return false;
        }

        $config = $this->configurations[$configuration];
        $baseUri = rtrim($config['base_uri'] ?? '', '/');

        if ($baseUri !== '' && str_starts_with($url, $baseUri)) {
            $filePath = substr($url, strlen($baseUri) + 1);
        } else {
            $filePath = ltrim($url, '/');
        }

        $filesystem = $this->getFilesystem($config['filesystem']);

        if (!$filesystem->fileExists($filePath)) {
            return false;
        }

        $filesystem->delete($filePath);

        return true;
    }

    /**
     * Check if a file exists in the configured filesystem.
     *
     * @throws FilesystemException
     */
    public function exists(string $url, ?string $configuration): bool
    {
        if ($configuration === null || !$this->hasConfiguration($configuration)) {
            return false;
        }

        $config = $this->configurations[$configuration];
        $baseUri = rtrim($config['base_uri'] ?? '', '/');

        if ($baseUri !== '' && str_starts_with($url, $baseUri)) {
            $filePath = substr($url, strlen($baseUri) + 1);
        } else {
            $filePath = ltrim($url, '/');
        }

        $filesystem = $this->getFilesystem($config['filesystem']);

        return $filesystem->fileExists($filePath);
    }

    private function getFilesystem(string $serviceId): FilesystemOperator
    {
        if (!$this->locator->has($serviceId)) {
            throw new InvalidArgumentException(sprintf('Filesystem service "%s" not found. Make sure it is configured correctly.', $serviceId));
        }

        $filesystem = $this->locator->get($serviceId);

        if (!$filesystem instanceof FilesystemOperator) {
            throw new InvalidArgumentException(sprintf('Service "%s" must implement "%s".', $serviceId, FilesystemOperator::class));
        }

        return $filesystem;
    }
}
