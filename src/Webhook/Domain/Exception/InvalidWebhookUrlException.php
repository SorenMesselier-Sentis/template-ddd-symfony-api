<?php

declare(strict_types=1);

namespace App\Webhook\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidWebhookUrlException extends InvalidArgumentException
{
    public static function malformed(string $url): self
    {
        return new self(sprintf('"%s" is not a valid URL.', $url));
    }

    public static function mustBeHttps(string $url): self
    {
        return new self(sprintf('Webhook URL "%s" must use https://.', $url));
    }

    public static function blockedHost(string $url): self
    {
        return new self(sprintf('Webhook URL "%s" targets a blocked host (localhost, or a private/reserved IP range).', $url));
    }

    public function errorCode(): string
    {
        return 'webhook.invalid_url';
    }
}
