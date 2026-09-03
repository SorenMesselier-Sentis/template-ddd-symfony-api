<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Filter\Filters;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFilterApplier;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use App\Webhook\Domain\Entity\WebhookSubscription;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineWebhookSubscriptionRepository implements WebhookSubscriptionRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(WebhookSubscription $subscription): void
    {
        $this->saveEntity($this->em, $subscription);
    }

    public function findById(WebhookSubscriptionId $id): ?WebhookSubscription
    {
        $subscription = $this->em->find(WebhookSubscription::class, $id);

        if (null !== $subscription && WebhookSubscriptionStatus::DELETED === $subscription->status()) {
            return null;
        }

        return $subscription;
    }

    public function findByIdIncludingDeleted(WebhookSubscriptionId $id): ?WebhookSubscription
    {
        $subscription = $this->em->find(WebhookSubscription::class, $id);

        return $subscription instanceof WebhookSubscription ? $subscription : null;
    }

    public function findActiveByEventName(string $eventName): array
    {
        /* @var list<WebhookSubscription> */
        return $this->em->createQueryBuilder()
            ->select('w')
            ->from(WebhookSubscription::class, 'w')
            ->where('w.status = :active')
            // Doctrine can't index into a JSON column portably across platforms, so the
            // event-name filter is a LIKE on the JSON-encoded list rather than a native array
            // "contains" check — cheap enough at the table sizes this template targets, and
            // WebhookSubscription::subscribesTo() is what actually enforces this in Domain terms
            // (this query is purely a pre-filter to avoid loading every subscription).
            ->andWhere('w.eventNames LIKE :eventName')
            ->setParameter('active', WebhookSubscriptionStatus::ACTIVE)
            ->setParameter('eventName', '%"'.$eventName.'"%')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(Filters $filters): array
    {
        $qb = $this->activeQueryBuilder();
        DoctrineFilterApplier::apply($qb, $filters, 'w');

        /* @var list<WebhookSubscription> */
        return $qb->getQuery()->getResult();
    }

    public function countByFilters(Filters $filters): int
    {
        $qb = $this->activeQueryBuilder()
            ->select('COUNT(w.id)');

        DoctrineFilterApplier::applyFilters($qb, $filters, 'w');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function activeQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('w')
            ->from(WebhookSubscription::class, 'w')
            ->where('w.status != :deleted')
            ->setParameter('deleted', WebhookSubscriptionStatus::DELETED);
    }
}
