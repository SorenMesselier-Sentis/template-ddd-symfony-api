<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetFeatureFlags;

use App\Shared\Domain\Bus\Query\Response;

final class FeatureFlagsResponse implements Response
{
    /**
     * @param list<FeatureFlagItemResponse> $flags
     */
    public function __construct(
        public readonly array $flags,
    ) {
    }
}
