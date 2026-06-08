<?php

declare(strict_types=1);

namespace App\Document\Domain\Enum;

enum DocumentStatus: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
