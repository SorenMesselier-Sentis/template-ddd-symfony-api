<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Command\RevokeApiClient;

use App\ApiClient\Domain\Exception\ApiClientNotFoundException;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\Repository\IssuedAccessTokenRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RevokeApiClientCommandHandler
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $repository,
        private readonly IssuedAccessTokenRepositoryInterface $tokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RevokeApiClientCommand $command): void
    {
        $id = ApiClientId::fromString($command->id);
        $entity = $this->repository->findById($id);

        if (null === $entity) {
            throw ApiClientNotFoundException::withId($command->id);
        }

        $entity->revoke();

        $this->repository->save($entity);
        $this->tokenRepository->revokeAllForClient($command->id);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('ApiClient revoked', ['id' => $command->id]);
    }
}
