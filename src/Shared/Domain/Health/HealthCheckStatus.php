<?php

declare(strict_types=1);

namespace App\Shared\Domain\Health;

final class HealthCheckStatus
{
    public function __construct(
        private readonly HealthCheckState $state,
        private readonly ?string $detail,
    ) {
    }

    public static function ok(?string $detail = null): self
    {
        return new self(HealthCheckState::OK, $detail);
    }

    public static function error(string $detail): self
    {
        return new self(HealthCheckState::ERROR, $detail);
    }

    public function isOk(): bool
    {
        return HealthCheckState::OK === $this->state;
    }

    public function isError(): bool
    {
        return HealthCheckState::ERROR === $this->state;
    }

    public function state(): HealthCheckState
    {
        return $this->state;
    }

    public function detail(): ?string
    {
        return $this->detail;
    }
}
