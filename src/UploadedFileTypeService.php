<?php

namespace Tiloweb\UploadedFileTypeBundle;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileTypeService
{
    private array $configurations;

    public function __construct(
        array $configurations
    ) {
        $this->configurations = $configurations;
    }

    public function hasConfiguration(string $configuration): bool {
        return isset($this->configurations[$configuration]);
    }

    /**
     * @throws \Exception
     */
    public function getConfiguration(string $configuration = 'default'): array {
        if(!$this->hasConfiguration($configuration)) {
            return $this->configurations[0];
        }

        return $this->configurations[$configuration];
    }

    public function upload(string $filename, UploadedFile $uploadedFile, ?string $configuration): ?string {
        if(!$configuration || !$this->hasConfiguration($configuration)) {
            return null;
        }

        $filePath = $this->configurations[$configuration]['path'].'/'.$filename;

        /**
         * @var \League\Flysystem\Filesystem $filesystem
         */
        $filesystem = $this->configurations[$configuration]['filesystem'];

        $stream = fopen($uploadedFile->getRealPath(), 'r+');

        $filesystem->writeStream($filePath, $stream);

        fclose($stream);

        return $this->configurations[$configuration]['base_uri'].$filePath;
    }
}
