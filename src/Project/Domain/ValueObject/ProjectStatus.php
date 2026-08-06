<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

enum ProjectStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
}
