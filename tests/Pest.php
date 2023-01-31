<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;

expect()->extend('toHaveEntry', function (string $message, array $context = []) {
    $this->toBeArray();
    $logs = $this->value;
    foreach ($logs as [$level, $text, $params]) {
        if ($message === $text) {
            foreach ($context as $key => $value) {
                if ($value !== ($params[$key] ?? null)) {
                    continue 2;
                }
            }
            return $this;
        }
    }

    throw new ExpectationFailedException("Failed asserting that logs contain `$message` with the given params.");
});
