<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Infrastructure\Http\RequestContext;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

final class MonologLogger implements LoggerInterface
{
    public function __construct(
        private readonly PsrLoggerInterface $logger,
        private readonly RequestContext $requestContext,
    ) {
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->withRequestContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->withRequestContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->withRequestContext($context));
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $this->withRequestContext($context));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function withRequestContext(array $context): array
    {
        $requestId = $this->requestContext->requestId();

        if ('' !== $requestId) {
            $context['request_id'] = $requestId;
        }

        return $context;
    }
}
