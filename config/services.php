<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tiloweb\UploadedFileTypeBundle\Form\UploadedFileTypeExtension;
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(UploadedFileTypeService::class)
        ->args([
            '$configurations' => [],
            '$locator' => service('service_container'),
        ])
        ->public()
        ->alias('tiloweb_uploaded_file_type.service', UploadedFileTypeService::class);

    $services->set(UploadedFileTypeExtension::class)
        ->args([
            service(UploadedFileTypeService::class),
        ])
        ->tag('form.type_extension', [
            'extended_type' => 'Symfony\Component\Form\Extension\Core\Type\FileType',
        ]);
};
