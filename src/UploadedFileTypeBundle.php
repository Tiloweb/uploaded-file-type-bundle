<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function dirname;
use function is_array;

/**
 * UploadedFileTypeBundle - Handles file uploads via Symfony forms with Flysystem storage.
 *
 * @author Thibault HENRY <thibault@henry.pro>
 */
class UploadedFileTypeBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->arrayNode('configurations')
            ->info('Define upload configurations for different use cases')
            ->useAttributeAsKey('name')
            ->requiresAtLeastOneElement()
            ->arrayPrototype()
            ->children()
            ->scalarNode('filesystem')
            ->isRequired()
            ->cannotBeEmpty()
            ->info('The Flysystem filesystem service ID (e.g., "oneup_flysystem.default_filesystem")')
            ->end()
            ->scalarNode('base_uri')
            ->defaultNull()
            ->info('The base URL to access uploaded files (e.g., "https://cdn.example.com")')
            ->example('https://cdn.example.com/uploads')
            ->end()
            ->scalarNode('path')
            ->defaultNull()
            ->info('The subdirectory path within the filesystem (e.g., "/images")')
            ->example('/images')
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $this->configureUploadService($config, $builder);
        $this->loadTwigTheme($builder);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function configureUploadService(array $config, ContainerBuilder $builder): void
    {
        $configurations = [];

        foreach ($config['configurations'] ?? [] as $name => $configItem) {
            $configurations[$name] = [
                'filesystem' => $configItem['filesystem'],
                'base_uri' => $configItem['base_uri'],
                'path' => $configItem['path'],
            ];
        }

        $builder->getDefinition(UploadedFileTypeService::class)
            ->setArgument('$configurations', $configurations);
    }

    private function loadTwigTheme(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('twig.form.resources')) {
            return;
        }

        $resources = $container->getParameter('twig.form.resources');
        if (!is_array($resources)) {
            $resources = [$resources];
        }

        $container->setParameter('twig.form.resources', array_merge(
            ['@UploadedFileType/form.html.twig'],
            $resources,
        ));
    }
}
