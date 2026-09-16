<?php

declare(strict_types=1);

namespace App\User\Application\Command\WithdrawConsent;

use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\ConsentType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class WithdrawConsentCommandHandler
{
    public function __construct(
        private readonly ConsentRepositoryInterface $repository,
        private readonly UserContextInterface $userContext,
    ) {
    }

    public function __invoke(WithdrawConsentCommand $command): void
    {
        $userId = $this->userContext->userId();
        $type = ConsentType::from($command->type);

        $consent = $this->repository->findByUserIdAndType($userId, $type);

        if (null === $consent) {
            // Withdrawing consent that was never given is a no-op — DELETE stays idempotent.
            return;
        }

        $consent->withdraw();
        $this->repository->save($consent);
    }
}
