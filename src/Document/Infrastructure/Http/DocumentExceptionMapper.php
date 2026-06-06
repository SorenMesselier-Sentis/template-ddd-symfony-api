<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http;

use App\Document\Domain\Exception\DocumentNotFoundException;
use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Exception\FileTooLargeException;
use App\Document\Domain\Exception\InvalidMimeTypeException;
use App\Document\Domain\Exception\InvalidMultipartFileSizeException;
use App\Document\Domain\Exception\InvalidPartSizeException;
use App\Document\Domain\Exception\InvalidPresignedUrlTtlException;
use App\Document\Domain\Exception\UploadSessionNotFoundException;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;

final class DocumentExceptionMapper implements ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof BucketNotFoundException
            || $exception instanceof DocumentNotFoundException
            || $exception instanceof InvalidMimeTypeException
            || $exception instanceof FileTooLargeException
            || $exception instanceof InvalidMultipartFileSizeException
            || $exception instanceof InvalidPartSizeException
            || $exception instanceof InvalidPresignedUrlTtlException
            || $exception instanceof UploadSessionNotFoundException;
    }

    public function resolve(\Throwable $exception): array
    {
        return match (true) {
            $exception instanceof BucketNotFoundException => [404, $exception->errorCode()],
            $exception instanceof DocumentNotFoundException => [404, $exception->errorCode()],
            $exception instanceof InvalidMimeTypeException => [422, $exception->errorCode()],
            $exception instanceof FileTooLargeException => [422, $exception->errorCode()],
            $exception instanceof InvalidMultipartFileSizeException => [422, $exception->errorCode()],
            $exception instanceof InvalidPartSizeException => [422, $exception->errorCode()],
            $exception instanceof InvalidPresignedUrlTtlException => [422, $exception->errorCode()],
            $exception instanceof UploadSessionNotFoundException => [404, $exception->errorCode()],
            default => [500, 'internal_server_error'],
        };
    }
}
