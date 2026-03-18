<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use App\Shared\Domain\Logging\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

final class MonologLogger implements LoggerInterface
{
    public function __construct(
        private readonly PsrLoggerInterface $logger
    ) {}

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
