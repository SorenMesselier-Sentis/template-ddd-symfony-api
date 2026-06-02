<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

interface ExceptionMapperInterface
{
    public function supports(\Throwable $exception): bool;

    /**
     * @return array{0: int, 1: string}
     */
    public function resolve(\Throwable $exception): array;
}
