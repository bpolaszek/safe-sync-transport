<?php

declare(strict_types=1);

namespace App;

final class DummyMessage
{
    public function __construct(
        public string $payload = '',
    ) {
    }
}
