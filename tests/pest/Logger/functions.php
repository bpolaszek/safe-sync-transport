<?php

declare(strict_types=1);

namespace Pest\Custom\Logger;

use Symfony\Component\ErrorHandler\BufferingLogger;

use function Pest\Custom\Symfony\container;

/**
 * @return array<int, mixed>
 */
function clean_logs(): array
{
    return container()->get(BufferingLogger::class)->cleanLogs(); // @phpstan-ignore-line
}
