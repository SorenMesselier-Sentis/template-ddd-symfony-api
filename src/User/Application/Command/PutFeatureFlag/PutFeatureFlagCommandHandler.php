<?php

declare(strict_types=1);

namespace App\User\Application\Command\PutFeatureFlag;

use App\Shared\Domain\FeatureFlag\FeatureFlag;
use App\Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class PutFeatureFlagCommandHandler
{
    public function __construct(
        private readonly FeatureFlagRepositoryInterface $repository,
    ) {
    }

    public function __invoke(PutFeatureFlagCommand $command): void
    {
        $this->repository->save(new FeatureFlag(
            key: $command->key,
            enabled: $command->enabled,
            description: $command->description,
            updatedAt: new \DateTimeImmutable(),
        ));
    }
}
