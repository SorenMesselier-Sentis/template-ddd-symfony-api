<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetFeatureFlags;

use App\Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetFeatureFlagsQueryHandler
{
    public function __construct(
        private readonly FeatureFlagRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetFeatureFlagsQuery $query): FeatureFlagsResponse
    {
        $flags = array_map(
            static fn ($flag) => new FeatureFlagItemResponse($flag),
            $this->repository->findAll(),
        );

        return new FeatureFlagsResponse($flags);
    }
}
