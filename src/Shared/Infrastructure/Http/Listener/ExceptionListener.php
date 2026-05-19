<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\AlreadyExistsException;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Exception\UnauthorizedException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\MissingTokenException;
use App\User\Domain\Exception\TokenExpiredException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException as LexikInvalidTokenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class ExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious() ?? $exception;
        }

        [$statusCode, $errorCode] = $this->resolveException($exception);

        $this->log($exception, $statusCode);

        $event->setResponse(new JsonResponse(
            data: [
                'error' => [
                    'code' => $errorCode,
                    'message' => $exception->getMessage(),
                ],
            ],
            status: $statusCode,
        ));
    }

    /**
     * @return array{0: int, 1: string}
     */
    public function resolveException(\Throwable $exception): array
    {
        return match (true) {
            $exception instanceof TokenExpiredException => [401, $exception->errorCode()],
            $exception instanceof InvalidTokenException => [401, $exception->errorCode()],
            $exception instanceof MissingTokenException => [401, $exception->errorCode()],
            $exception instanceof ForbiddenException => [403, $exception->errorCode()],
            $exception instanceof ExpiredTokenException => [401, 'token_expired'],
            $exception instanceof LexikInvalidTokenException => [401, 'invalid_token'],
            $exception instanceof NotFoundException => [404, $exception->errorCode()],
            $exception instanceof AlreadyExistsException => [409, $exception->errorCode()],
            $exception instanceof InvalidArgumentException => [400, $exception->errorCode()],
            $exception instanceof UnauthorizedException => [401, $exception->errorCode()],
            $exception instanceof DomainException => [422, $exception->errorCode()],
            $exception instanceof NotFoundHttpException => [404, 'route.not_found'],
            $exception instanceof MethodNotAllowedHttpException => [405, 'method.not_allowed'],
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

            return;
        }

        $this->logger->warning($exception->getMessage(), [
            'exception' => $exception::class,
        ]);
    }
}
