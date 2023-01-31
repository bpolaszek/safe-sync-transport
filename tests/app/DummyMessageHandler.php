<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Service\ResetInterface;

#[AsMessageHandler]
final class DummyMessageHandler implements ResetInterface
{
    private int $attempts = 0;
    public int $minAttempts = 0;

    public ?DummyMessage $lastMessage = null;

    public function __invoke(DummyMessage $message): void
    {
        $this->attempts++;
        if ($this->attempts <= $this->minAttempts) {
            throw new \RuntimeException("Attempt: {$this->attempts}");
        }
        $this->lastMessage = $message;
    }

    public function reset(): void
    {
        $this->attempts = 0;
        $this->lastMessage = null;
    }
}
