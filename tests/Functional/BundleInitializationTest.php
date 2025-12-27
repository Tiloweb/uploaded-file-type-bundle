<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeService;

final class BundleInitializationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    public function test_bundle_loads_correctly(): void
    {
        $container = self::getContainer();

        self::assertTrue($container->has(UploadedFileTypeService::class));
    }

    public function test_service_is_accessible(): void
    {
        $container = self::getContainer();

        $service = $container->get(UploadedFileTypeService::class);

        self::assertInstanceOf(UploadedFileTypeService::class, $service);
    }

    public function test_configurations_are_loaded(): void
    {
        $container = self::getContainer();

        /** @var UploadedFileTypeService $service */
        $service = $container->get(UploadedFileTypeService::class);

        self::assertTrue($service->hasConfiguration('default'));
        self::assertTrue($service->hasConfiguration('avatars'));
        self::assertFalse($service->hasConfiguration('nonexistent'));
    }

    public function test_twig_form_theme_is_registered(): void
    {
        $container = self::getContainer();

        $resources = $container->getParameter('twig.form.resources');

        self::assertIsArray($resources);
        self::assertContains('@UploadedFileType/form.html.twig', $resources);
    }
}
