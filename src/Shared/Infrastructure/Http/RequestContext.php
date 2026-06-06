<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

final class RequestContext
{
    private string $requestId = '';

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $id): void
    {
        $this->requestId = $id;
    }
}
