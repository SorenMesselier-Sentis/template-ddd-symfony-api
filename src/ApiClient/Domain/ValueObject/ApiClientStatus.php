<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\ValueObject;

enum ApiClientStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
    case DELETED = 'deleted';
}
