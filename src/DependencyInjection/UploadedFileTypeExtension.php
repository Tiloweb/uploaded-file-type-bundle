<?php

namespace Tiloweb\UploadedFileTypeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class UploadedFileTypeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');


        $configuration = $this->getConfiguration($configs, $container);

        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition("tiloweb_uploaded_file_type.uploaded_file_type_service");

        $config['configurations'] = array_map(function(array $config) {
            $config['filesystem'] = new Reference($config['filesystem']);

            return $config;
        }, $config['configurations']);

        $definition->setArgument(0, $config['configurations']);

        $this->loadTwigTheme($container);
    }

    private function loadTwigTheme(ContainerBuilder $container)
    {
        if (!$container->hasParameter('twig.form.resources')) {
            return;
        }

        $container->setParameter('twig.form.resources', array_merge(
            [
                '@UploadedFileType/form.html.twig'
            ],
            $container->getParameter('twig.form.resources')
        ));
    }
}