<?php

declare(strict_types=1);

namespace App\Shared\Domain\Logging;

interface LoggerInterface
{
    /**
     * @param array<int,mixed> $context
     */
    public function info(string $message, array $context = []): void;
    /**
     * @param array<int,mixed> $context
     */
    public function warning(string $message, array $context = []): void;
    /**
     * @param array<int,mixed> $context
     */
    public function error(string $message, array $context = []): void;
    /**
     * @param array<int,mixed> $context
     */
    public function debug(string $message, array $context = []): void;
}
