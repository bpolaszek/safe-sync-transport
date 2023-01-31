<?php

declare(strict_types=1);

namespace Pest\Custom\Symfony;

use Symfony\Component\DependencyInjection\ContainerInterface;

final class GenericContainer implements ContainerInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @template T
     *
     * @psalm-var class-string<T> $id
     *
     * @return T
     */
    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): object
    {
        return $this->container->get($id, $invalidBehavior);
    }

    public function set(string $id, ?object $service): void
    {
        $this->container->set($id, $service);
    }

    public function initialized(string $id): bool
    {
        return $this->container->initialized($id);
    }

    public function getParameter(string $name): mixed
    {
        return $this->container->getParameter($name);
    }

    public function hasParameter(string $name): bool
    {
        return $this->container->hasParameter($name);
    }

    public function setParameter(string $name, $value): void
    {
        $this->container->set($name, $value);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}
