<?php

namespace Tiloweb\UploadedFileTypeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('uploaded_file_type');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('configurations')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name', false)
                    ->arrayPrototype()
                        ->addDefaultsIfNotSet()
                        ->ignoreExtraKeys()
                        ->children()
                            ->scalarNode('filesystem')
                                ->defaultValue('@oneup_flysystem.default_adapter_adapter')
                            ->end()
                            ->scalarNode('base_uri')->defaultNull()->end()
                            ->scalarNode('path')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}