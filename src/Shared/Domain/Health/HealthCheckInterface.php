<?php

declare(strict_types=1);

namespace App\Shared\Domain\Health;

interface HealthCheckInterface
{
    public function name(): string;

    public function check(): HealthCheckStatus;
}
