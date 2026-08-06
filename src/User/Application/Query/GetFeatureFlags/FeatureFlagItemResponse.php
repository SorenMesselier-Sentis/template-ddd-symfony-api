<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetFeatureFlags;

use App\Shared\Domain\Bus\Query\Response;
use App\Shared\Domain\FeatureFlag\FeatureFlag;

final class FeatureFlagItemResponse implements Response
{
    public readonly string $key;
    public readonly bool $enabled;
    public readonly ?string $description;
    public readonly string $updatedAt;

    public function __construct(FeatureFlag $flag)
    {
        $this->key = $flag->key;
        $this->enabled = $flag->enabled;
        $this->description = $flag->description;
        $this->updatedAt = $flag->updatedAt->format(\DateTimeInterface::ATOM);
    }
}
