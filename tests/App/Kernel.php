<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle\Tests\App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new UploadedFileTypeBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/uploaded_file_type_bundle/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/uploaded_file_type_bundle/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
            'form' => [
                'enabled' => true,
            ],
            'session' => [
                'handler_id' => null,
                'cookie_secure' => 'auto',
                'cookie_samesite' => 'lax',
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
            'form_themes' => ['@UploadedFileType/form.html.twig'],
        ]);

        // Register test filesystem
        $container->services()
            ->set('test.filesystem', TestFilesystem::class)
            ->public();

        $container->extension('uploaded_file_type', [
            'configurations' => [
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
            ],
        ]);
    }
}
