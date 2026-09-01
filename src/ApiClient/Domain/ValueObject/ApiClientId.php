<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

/**
 * Doubles as the OAuth2 `client_id` presented by callers — no separate public identifier field.
 */
final class ApiClientId extends Uuid
{
}
