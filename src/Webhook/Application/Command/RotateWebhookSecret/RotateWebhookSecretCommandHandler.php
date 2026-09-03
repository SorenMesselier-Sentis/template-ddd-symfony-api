<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\RotateWebhookSecret;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RotateWebhookSecretCommandHandler
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    /**
     * @return array{secret: string}
     */
    public function __invoke(RotateWebhookSecretCommand $command): array
    {
        $entity = $this->repository->findById(WebhookSubscriptionId::fromString($command->id));

        if (null === $entity) {
            throw WebhookSubscriptionNotFoundException::withId($command->id);
        }

        $secret = bin2hex(random_bytes(32));
        $entity->rotateSecret($secret);

        $this->repository->save($entity);
        $this->eventBus->publish(...$entity->pullDomainEvents());

        return ['secret' => $secret];
    }
}
