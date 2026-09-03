<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\DeleteWebhookSubscription;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteWebhookSubscriptionCommandHandler
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(DeleteWebhookSubscriptionCommand $command): void
    {
        $entity = $this->repository->findById(WebhookSubscriptionId::fromString($command->id));

        if (null === $entity) {
            throw WebhookSubscriptionNotFoundException::withId($command->id);
        }

        $entity->delete();

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());
    }
}
