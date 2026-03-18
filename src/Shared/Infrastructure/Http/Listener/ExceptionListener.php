<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\AlreadyExistsException;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Exception\UnauthorizedException;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        [$statusCode, $errorCode] = $this->resolveException($exception);

        $this->log($exception, $statusCode);

        $event->setResponse(new JsonResponse(
            data: [
                'error' => [
                    'code' => $errorCode,
                    'message' => $exception->getMessage(),
                ]
            ]
        ));
    }

    public function resolveException(\Throwable $exception): array
    {
        return match (true) {
            $exception instanceof NotFoundException => [404, $exception->errorCode()],
            $exception instanceof AlreadyExistsException  => [409, $exception->errorCode()],
            $exception instanceof InvalidArgumentException => [400, $exception->errorCode()],
            $exception instanceof UnauthorizedException => [401, $exception->errorCode()],
            $exception instanceof DomainException => [422, $exception->errorCode()],
            $exception instanceof HttpExceptionInterface => [$exception->getStatusCode(), 'http_error'],
            default => [500, 'internal_server_error'],
        };
    }

    public function log(\Throwable $exception, int $statusCode): void
    {
        if ($statusCode >= 500) {
            $this->logger->error($exception->getMessage(), [
                'exception' => $exception::class,
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return;

        $this->logger->warning($exception->getMessage(), [
            'exception' => $exception::class
        ]);
    }
}
