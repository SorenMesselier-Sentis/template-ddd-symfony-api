<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http;

use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Exception\FileTooLargeException;
use App\Document\Domain\Exception\InvalidMimeTypeException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;

final class DocumentExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof BucketNotFoundException
            || $exception instanceof InvalidMimeTypeException
            || $exception instanceof FileTooLargeException;
    }

    public function resolve(\Throwable $exception): array
    {
        return match (true) {
            $exception instanceof BucketNotFoundException => [404, $exception->errorCode()],
            $exception instanceof InvalidMimeTypeException => [422, $exception->errorCode()],
            $exception instanceof FileTooLargeException => [422, $exception->errorCode()],
            default => [500, 'internal_server_error'],
        };
    }
}
