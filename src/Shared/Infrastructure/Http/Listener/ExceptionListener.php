<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\AlreadyExistsException;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\FeatureDisabledException;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Exception\IdempotencyKeyConflictException;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Exception\RateLimitExceededException;
use App\Shared\Domain\Exception\UnauthorizedException;
use App\Shared\Domain\Exception\ValidationException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException as LexikInvalidTokenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ExceptionListener
{
    /**
     * @param iterable<ExceptionMapperInterface> $mappers
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly iterable $mappers,
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

        $errorPayload = [
            'code' => $errorCode,
            'message' => $exception->getMessage(),
        ];

        if ($exception instanceof ValidationException) {
            $errorPayload['errors'] = array_map(
                static fn ($error) => [
                    'field' => $error->field,
                    'code' => $error->code,
                    'message' => $error->message,
                ],
                $exception->errors(),
            );
        }

        $response = new JsonResponse(
            data: ['error' => $errorPayload],
            status: $statusCode,
        );

        if ($exception instanceof RateLimitExceededException && null !== $exception->retryAfterSeconds) {
            $response->headers->set('Retry-After', (string) $exception->retryAfterSeconds);
        }

        $event->setResponse($response);
    }

    /**
     * @return array{0: int, 1: string}
     */
    public function resolveException(\Throwable $exception): array
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($exception)) {
                return $mapper->resolve($exception);
            }
        }

        return match (true) {
            $exception instanceof ValidationException => [422, 'validation_error'],
            $exception instanceof ForbiddenException => [403, $exception->errorCode()],
            $exception instanceof FeatureDisabledException => [403, $exception->errorCode()],
            $exception instanceof AccessDeniedException => [403, 'forbidden'],
            $exception instanceof ExpiredTokenException => [401, 'token_expired'],
            $exception instanceof LexikInvalidTokenException => [401, 'invalid_token'],
            $exception instanceof NotFoundException => [404, $exception->errorCode()],
            $exception instanceof AlreadyExistsException => [409, $exception->errorCode()],
            $exception instanceof IdempotencyKeyConflictException => [409, $exception->errorCode()],
            $exception instanceof InvalidArgumentException => [400, $exception->errorCode()],
            $exception instanceof UnauthorizedException => [401, $exception->errorCode()],
            $exception instanceof RateLimitExceededException => [429, $exception->errorCode()],
            $exception instanceof DomainException => [422, 'domain_error'],
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
                'exception' => $exception,
            ]);

            return;
        }

        $this->logger->warning($exception->getMessage(), [
            'exception' => $exception::class,
        ]);
    }
}
