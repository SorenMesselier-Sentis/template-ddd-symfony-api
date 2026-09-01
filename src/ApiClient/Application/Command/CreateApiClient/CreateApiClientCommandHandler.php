<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Command\CreateApiClient;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Domain\ValueObject\HashedClientSecret;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateApiClientCommandHandler
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{id: string, secret: string}
     */
    public function __invoke(CreateApiClientCommand $command): array
    {
        $this->logger->info('Creating ApiClient', ['id' => $command->id]);

        $plainSecret = bin2hex(random_bytes(32));

        $entity = ApiClient::create(
            id: ApiClientId::fromString($command->id),
            name: $command->name,
            secretHash: HashedClientSecret::fromPlainSecret($plainSecret),
            scopes: $command->scopes,
        );

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('ApiClient created', ['id' => $command->id]);

        return ['id' => $command->id, 'secret' => $plainSecret];
    }
}
