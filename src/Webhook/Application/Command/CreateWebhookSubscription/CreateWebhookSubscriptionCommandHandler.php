<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\CreateWebhookSubscription;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Webhook\Domain\Entity\WebhookSubscription;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookUrl;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateWebhookSubscriptionCommandHandler
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{id: string, secret: string}
     */
    public function __invoke(CreateWebhookSubscriptionCommand $command): array
    {
        $this->logger->info('Creating webhook subscription', ['id' => $command->id]);

        $secret = bin2hex(random_bytes(32));

        $entity = WebhookSubscription::create(
            id: WebhookSubscriptionId::fromString($command->id),
            name: $command->name,
            url: WebhookUrl::fromString($command->url),
            secret: $secret,
            eventNames: $command->eventNames,
        );

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        $this->logger->info('Webhook subscription created', ['id' => $command->id]);

        return ['id' => $command->id, 'secret' => $secret];
    }
}
