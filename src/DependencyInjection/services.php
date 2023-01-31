<?php

declare(strict_types=1);

namespace BenTools\SafeSyncTransport\DependencyInjection;

use BenTools\SafeSyncTransport\Transport\SafeSyncTransport;
use BenTools\SafeSyncTransport\Transport\SafeSyncTransportFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $services
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $services->set(SafeSyncTransport::class);
    $services->set(SafeSyncTransportFactory::class);
};
