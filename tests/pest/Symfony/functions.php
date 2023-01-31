<?php

declare(strict_types=1);

namespace Pest\Custom\Symfony;

use LogicException;
use Pest\Exceptions\ShouldNotHappen;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

function app(bool $reInstanciate = false): KernelInterface
{
    static $kernel;

    if (null === $kernel || $reInstanciate) {
        $testCase = new class () extends KernelTestCase {
            public function getKernel(): KernelInterface
            {
                self::bootKernel();

                return self::$kernel;
            }
        };
        $kernel = $testCase->getKernel();
    }

    return $kernel;
}

function container(?KernelInterface $app = null): GenericContainer
{
    $app = $app ?? app();
    $container = $app->getContainer()->get('test.service_container', ContainerInterface::NULL_ON_INVALID_REFERENCE);

    if (!$container instanceof ContainerInterface) {
        throw new ShouldNotHappen(new LogicException('Unable to retrieve the test container.'));
    }

    return new GenericContainer($container);
}
