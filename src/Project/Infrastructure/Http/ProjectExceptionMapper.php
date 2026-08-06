<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http;

use App\Project\Domain\Exception\ProjectHasActiveTasksException;
use App\Project\Domain\Exception\ProjectNotActiveException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;

final class ProjectExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ProjectNotActiveException
            || $exception instanceof ProjectHasActiveTasksException;
    }

    public function resolve(\Throwable $exception): array
    {
        return match (true) {
            $exception instanceof ProjectNotActiveException => [409, $exception->errorCode()],
            $exception instanceof ProjectHasActiveTasksException => [409, $exception->errorCode()],
            default => [500, 'internal_server_error'],
        };
    }
}
