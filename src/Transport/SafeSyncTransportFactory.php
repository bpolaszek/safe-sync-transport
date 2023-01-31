<?php

declare(strict_types=1);

namespace BenTools\SafeSyncTransport\Transport;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class SafeSyncTransportFactory implements TransportFactoryInterface
{
    private SafeSyncTransport $transport;

    public function __construct(SafeSyncTransport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        return $this->transport;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'safe-sync://');
    }
}
