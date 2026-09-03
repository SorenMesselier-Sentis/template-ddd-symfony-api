<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class ApiClientNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('API client with id "%s" was not found.', $id));
    }

    public function errorCode(): string
    {
        return 'api_client.not_found';
    }
}
