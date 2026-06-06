<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use App\User\Domain\Exception\InsufficientPrivilegesException;
use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\MissingTokenException;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Exception\TokenRevokedException;

final class UserExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof InsufficientPrivilegesException
            || $exception instanceof InvalidTokenException
            || $exception instanceof MissingTokenException
            || $exception instanceof TokenExpiredException
            || $exception instanceof TokenRevokedException;
    }

    public function resolve(\Throwable $exception): array
    {
        return match (true) {
            $exception instanceof InsufficientPrivilegesException => [403, $exception->errorCode()],
            $exception instanceof InvalidTokenException => [401, $exception->errorCode()],
            $exception instanceof MissingTokenException => [401, $exception->errorCode()],
            $exception instanceof TokenExpiredException => [401, $exception->errorCode()],
            $exception instanceof TokenRevokedException => [401, $exception->errorCode()],
            default => [500, 'internal_server_error'],
        };
    }
}
