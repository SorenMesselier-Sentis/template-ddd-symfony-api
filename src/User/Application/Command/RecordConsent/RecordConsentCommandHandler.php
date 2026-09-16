<?php

declare(strict_types=1);

namespace App\User\Application\Command\RecordConsent;

use App\User\Domain\Entity\Consent;
use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\ConsentId;
use App\User\Domain\ValueObject\ConsentType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RecordConsentCommandHandler
{
    public function __construct(
        private readonly ConsentRepositoryInterface $repository,
        private readonly UserContextInterface $userContext,
    ) {
    }

    public function __invoke(RecordConsentCommand $command): void
    {
        $userId = $this->userContext->userId();
        $type = ConsentType::from($command->type);

        $consent = $this->repository->findByUserIdAndType($userId, $type);

        if (null === $consent) {
            $consent = Consent::give(
                id: ConsentId::random(),
                userId: $userId,
                type: $type,
                version: $command->version,
            );
        } else {
            $consent->renew($command->version);
        }

        $this->repository->save($consent);
    }
}
