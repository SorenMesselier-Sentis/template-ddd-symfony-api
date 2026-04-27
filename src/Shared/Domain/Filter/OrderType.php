<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

enum OrderType: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}
