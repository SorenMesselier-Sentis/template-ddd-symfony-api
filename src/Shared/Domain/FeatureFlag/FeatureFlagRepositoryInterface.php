<?php

declare(strict_types=1);

namespace App\Shared\Domain\FeatureFlag;

interface FeatureFlagRepositoryInterface
{
    public function findByKey(string $key): ?FeatureFlag;

    /**
     * @return list<FeatureFlag>
     */
    public function findAll(): array;

    public function save(FeatureFlag $flag): void;

    /**
     * Unregistered flags are treated as disabled — a flag only takes effect
     * once explicitly created (see PutFeatureFlagCommand).
     */
    public function isEnabled(string $key): bool;
}
