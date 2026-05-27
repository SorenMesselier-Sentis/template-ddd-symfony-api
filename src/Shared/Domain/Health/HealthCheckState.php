<?php

declare(strict_types=1);

namespace App\Shared\Domain\Health;

enum HealthCheckState: string
{
    case OK = 'ok';
    case ERROR = 'error';
}