<?php

declare(strict_types=1);

namespace BenTools\SafeSyncTransport\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Throwable;

use function get_class;
use function usleep;

final class SafeSyncTransport implements TransportInterface
{
    private MessageBusInterface $messageBus;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;

    public function __construct(
        MessageBusInterface $messageBus,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger,
    ) {
        $this->messageBus = $messageBus;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger ?? new NullLogger();
    }

    public function get(): iterable
    {
        throw new InvalidArgumentException('You cannot receive messages from the Messenger SafeSyncTransport.');
    }

    public function stop(): void
    {
        throw new InvalidArgumentException('You cannot call stop() on the Messenger SafeSyncTransport.');
    }

    public function ack(Envelope $envelope): void
    {
        throw new InvalidArgumentException('You cannot call ack() on the Messenger SafeSyncTransport.');
    }

    public function reject(Envelope $envelope): void
    {
        throw new InvalidArgumentException('You cannot call reject() on the Messenger SafeSyncTransport.');
    }

    public function send(Envelope $envelope): Envelope
    {
        /** @var SentStamp|null $sentStamp */
        $sentStamp = $envelope->last(SentStamp::class);
        $transportName = null === $sentStamp ? 'sync' : ($sentStamp->getSenderAlias() ?: $sentStamp->getSenderClass());
        $delayStamp = $envelope->last(DelayStamp::class);
        if ($delayStamp instanceof DelayStamp) {
            usleep($delayStamp->getDelay() * 1000);
        }
        $this->handleMessage($envelope, $transportName);

        return $envelope;
    }

    private function handleMessage(Envelope $envelope, string $transportName): void
    {
        $event = new WorkerMessageReceivedEvent($envelope, $transportName);
        $envelope = $event->getEnvelope();

        if (!$event->shouldHandle()) {
            return;
        }

        try {
            $envelope = $this->messageBus->dispatch($envelope->with(new ReceivedStamp($transportName)));
        } catch (Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $envelope = $e->getEnvelope();
            }

            $failedEvent = new WorkerMessageFailedEvent($envelope, $transportName, $e);

            $this->eventDispatcher->dispatch($failedEvent);

            return;
        }

        $handledEvent = new WorkerMessageHandledEvent($envelope, $transportName);
        $envelope = $handledEvent->getEnvelope();

        $this->logger->info('{class} was handled successfully (acknowledging to transport).', [
            'class' => get_class($envelope->getMessage()),
        ]);
    }
}
