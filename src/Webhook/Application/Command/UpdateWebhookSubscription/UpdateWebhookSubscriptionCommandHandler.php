<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\UpdateWebhookSubscription;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookUrl;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateWebhookSubscriptionCommandHandler
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateWebhookSubscriptionCommand $command): void
    {
        $entity = $this->repository->findById(WebhookSubscriptionId::fromString($command->id));

        if (null === $entity) {
            throw WebhookSubscriptionNotFoundException::withId($command->id);
        }

        $entity->update($command->name, WebhookUrl::fromString($command->url), $command->eventNames);

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());
    }
}
