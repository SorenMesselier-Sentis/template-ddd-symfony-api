<?php

declare(strict_types=1);

namespace App\Webhook\Application\Query\GetWebhookSubscription;

use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWebhookSubscriptionQueryHandler
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetWebhookSubscriptionQuery $query): WebhookSubscriptionResponse
    {
        $entity = $this->repository->findById(WebhookSubscriptionId::fromString($query->id));

        if (null === $entity) {
            throw WebhookSubscriptionNotFoundException::withId($query->id);
        }

        return new WebhookSubscriptionResponse($entity);
    }
}
