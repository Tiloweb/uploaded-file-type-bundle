<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new class implements ConfigurationInterface {
            public function getConfigTreeBuilder(): TreeBuilder
            {
                $treeBuilder = new TreeBuilder('uploaded_file_type');
                $rootNode = $treeBuilder->getRootNode();

                $rootNode
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
                                    ->end()
                                    ->scalarNode('base_uri')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('path')
                                        ->defaultNull()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ;

                return $treeBuilder;
            }
        };
    }

    public function test_valid_minimal_configuration(): void
    {
        $config = $this->processConfig([
            'uploaded_file_type' => [
                'configurations' => [
                    'default' => [
                        'filesystem' => 'oneup_flysystem.default_filesystem',
                    ],
                ],
            ],
        ]);

        self::assertArrayHasKey('configurations', $config);
        self::assertArrayHasKey('default', $config['configurations']);
        self::assertSame('oneup_flysystem.default_filesystem', $config['configurations']['default']['filesystem']);
        self::assertNull($config['configurations']['default']['base_uri']);
        self::assertNull($config['configurations']['default']['path']);
    }

    public function test_valid_full_configuration(): void
    {
        $config = $this->processConfig([
            'uploaded_file_type' => [
                'configurations' => [
                    'default' => [
                        'filesystem' => 'oneup_flysystem.default_filesystem',
                        'base_uri' => 'https://cdn.example.com',
                        'path' => '/uploads/images',
                    ],
                ],
            ],
        ]);

        self::assertSame('oneup_flysystem.default_filesystem', $config['configurations']['default']['filesystem']);
        self::assertSame('https://cdn.example.com', $config['configurations']['default']['base_uri']);
        self::assertSame('/uploads/images', $config['configurations']['default']['path']);
    }

    public function test_multiple_configurations(): void
    {
        $config = $this->processConfig([
            'uploaded_file_type' => [
                'configurations' => [
                    'images' => [
                        'filesystem' => 'oneup_flysystem.images_filesystem',
                        'base_uri' => 'https://images.example.com',
                        'path' => '/images',
                    ],
                    'documents' => [
                        'filesystem' => 'oneup_flysystem.documents_filesystem',
                        'base_uri' => 'https://docs.example.com',
                        'path' => '/documents',
                    ],
                ],
            ],
        ]);

        self::assertCount(2, $config['configurations']);
        self::assertArrayHasKey('images', $config['configurations']);
        self::assertArrayHasKey('documents', $config['configurations']);
    }

    public function test_empty_configurations_throws_exception(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfig([
            'uploaded_file_type' => [
                'configurations' => [],
            ],
        ]);
    }

    public function test_missing_filesystem_throws_exception(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfig([
            'uploaded_file_type' => [
                'configurations' => [
                    'default' => [
                        'base_uri' => 'https://cdn.example.com',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $configs
     *
     * @return array<string, mixed>
     */
    private function processConfig(array $configs): array
    {
        return $this->processor->processConfiguration(
            $this->configuration,
            $configs,
        );
    }
}
