<?php

declare(strict_types=1);

namespace BenTools\SafeSyncTransport\Tests;

use App\DummyMessage;
use App\DummyMessageHandler;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\MessageBusInterface;

use function afterEach;
use function beforeEach;
use function expect;
use function microtime;
use function Pest\Custom\Logger\clean_logs;
use function Pest\Custom\Symfony\container;

beforeEach(fn () => container()->get(DummyMessageHandler::class)->reset()); // @phpstan-ignore-line
afterEach(fn () => clean_logs());

it('handles the message synchronously', function () {
    /** @var MessageBusInterface $messageBus */
    $messageBus = container()->get(MessageBusInterface::class);
    /** @var DummyMessageHandler $handler */
    $handler = container()->get(DummyMessageHandler::class);

    // When
    $messageBus->dispatch(new DummyMessage('covfefe'));

    // Then
    expect($handler->lastMessage)->toBeInstanceOf(DummyMessage::class)
        ->and($handler->lastMessage?->payload)->toBe('covfefe');
});

it('retries when it fails', function () {
    /** @var MessageBusInterface $messageBus */
    $messageBus = container()->get(MessageBusInterface::class);
    /** @var DummyMessageHandler $handler */
    $handler = container()->get(DummyMessageHandler::class);

    // Given
    $handler->minAttempts = 1;
    $start = microtime(true);

    // When
    $messageBus->dispatch(new DummyMessage('covfefe'));
    $end = microtime(true);

    // Then
    expect(clean_logs())
        ->toHaveEntry(
            'Error thrown while handling message {class}. Sending for retry #{retryCount} using {delay} ms delay. Error: "{error}"', // phpcs:ignore
            [
                'class' => DummyMessage::class,
                'retryCount' => 1,
                'delay' => 1000,
                'error' => 'Handling "App\DummyMessage" failed: Attempt: 1',
            ],
        )
        ->and($handler->lastMessage)->toBeInstanceOf(DummyMessage::class)
        ->and($handler->lastMessage?->payload)->toBe('covfefe')
        ->and($end - $start)->toBeGreaterThan(0.1);
});

it('retries several times, with a growing delay', function () {
    /** @var MessageBusInterface $messageBus */
    $messageBus = container()->get(MessageBusInterface::class);
    /** @var DummyMessageHandler $handler */
    $handler = container()->get(DummyMessageHandler::class);

    // Given
    $handler->minAttempts = 3;
    $start = microtime(true);

    // When
    $messageBus->dispatch(new DummyMessage('covfefe'));
    $end = microtime(true);

    // Then
    $logs = clean_logs();
    expect($logs)
        ->toHaveEntry(
            'Error thrown while handling message {class}. Sending for retry #{retryCount} using {delay} ms delay. Error: "{error}"', // phpcs:ignore
            [
                'class' => DummyMessage::class,
                'retryCount' => 2,
                'delay' => 2000,
                'error' => 'Handling "App\DummyMessage" failed: Attempt: 2',
            ],
        )
        ->and($logs)->toHaveEntry(
            'Error thrown while handling message {class}. Sending for retry #{retryCount} using {delay} ms delay. Error: "{error}"', // phpcs:ignore
            [
                'class' => DummyMessage::class,
                'retryCount' => 3,
                'delay' => 4000,
                'error' => 'Handling "App\DummyMessage" failed: Attempt: 3',
            ],
        )
        ->and($handler->lastMessage)->toBeInstanceOf(DummyMessage::class)
        ->and($handler->lastMessage?->payload)->toBe('covfefe')
        ->and($end - $start)->toBeGreaterThan(7);
});

it('pushes the message away to a failure transport when max retries have been reached', function () {
    /** @var MessageBusInterface $messageBus */
    $messageBus = container()->get(MessageBusInterface::class);
    /** @var DummyMessageHandler $handler */
    $handler = container()->get(DummyMessageHandler::class);
    /** @phpstan-ignore-next-line */
    $commandTester = new CommandTester(container()->get('console.command.messenger_consume_messages'));

    // Given
    $handler->minAttempts = 4; // Higher than retry_strategy.max_retries

    // When
    $messageBus->dispatch(new DummyMessage('covfefe'));

    // Then
    expect($handler->lastMessage)->toBeNull();

    // Now, consume failed transport
    $handler->minAttempts = 1;
    $commandTester->execute(
        ['receivers' => ['failed'], '--limit' => 1, '--time-limit' => 1, '--no-reset' => true],
        ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE],
    );
    expect($handler->lastMessage)
        ->toBeInstanceOf(DummyMessage::class)
        ->and($handler->lastMessage?->payload)->toBe('covfefe');
});
