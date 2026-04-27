<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

 enum FilterOperator: string
{
    case EQUAL = 'eq';
    case GREATER_THAN_OR_EQUAL = '>=';
    case LESS_THAN_OR_EQUAL = '<=';
    case IN = 'IN';
}
