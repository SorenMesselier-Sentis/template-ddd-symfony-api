<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case DELETED = 'deleted';
}
