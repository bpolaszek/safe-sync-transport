<?php

declare(strict_types=1);

namespace BenTools\SafeSyncTransport;

use BenTools\SafeSyncTransport\DependencyInjection\SafeSyncTransportExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SafeSyncTransportBundle extends Bundle
{
    protected function getContainerExtensionClass(): string
    {
        return SafeSyncTransportExtension::class;
    }
}
