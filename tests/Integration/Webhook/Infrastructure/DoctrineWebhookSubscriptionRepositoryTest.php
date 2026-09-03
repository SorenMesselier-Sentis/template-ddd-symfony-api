<?php

declare(strict_types=1);

namespace App\Tests\Integration\Webhook\Infrastructure;

use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Infrastructure\Persistence\Doctrine\Repository\DoctrineWebhookSubscriptionRepository;

final class DoctrineWebhookSubscriptionRepositoryTest extends IntegrationTestCase
{
    private DoctrineWebhookSubscriptionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineWebhookSubscriptionRepository($this->em);
    }

    public function testItSavesAndFindsAWebhookSubscription(): void
    {
        $entity = WebhookSubscriptionMother::create(eventNames: ['document.uploaded', 'user.created']);
        $this->repository->save($entity);

        $found = $this->repository->findById($entity->id());

        $this->assertNotNull($found);
        $this->assertTrue($entity->id()->equals($found->id()));
        $this->assertSame($entity->name(), $found->name());
        $this->assertSame($entity->url()->value(), $found->url()->value());
        $this->assertSame($entity->secret(), $found->secret());
        $this->assertSame(['document.uploaded', 'user.created'], $found->eventNames());
    }

    public function testItReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repository->findById(WebhookSubscriptionId::random()));
    }

    public function testFindByIdReturnsNullForADeletedSubscription(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $entity->delete();
        $this->repository->save($entity);

        $this->assertNull($this->repository->findById($entity->id()));
        $this->assertNotNull($this->repository->findByIdIncludingDeleted($entity->id()));
    }

    public function testFindActiveByEventNameOnlyReturnsActiveSubscriptionsSubscribedToThatEvent(): void
    {
        $matchingActive = WebhookSubscriptionMother::create(eventNames: ['document.uploaded']);

        $matchingDisabled = WebhookSubscriptionMother::create(eventNames: ['document.uploaded']);
        $matchingDisabled->disable();

        $matchingDeleted = WebhookSubscriptionMother::create(eventNames: ['document.uploaded']);
        $matchingDeleted->delete();

        $nonMatching = WebhookSubscriptionMother::create(eventNames: ['user.created']);

        $this->repository->save($matchingActive);
        $this->repository->save($matchingDisabled);
        $this->repository->save($matchingDeleted);
        $this->repository->save($nonMatching);

        $found = $this->repository->findActiveByEventName('document.uploaded');
        $foundIds = array_map(static fn ($s) => $s->id()->value(), $found);

        $this->assertContains($matchingActive->id()->value(), $foundIds);
        $this->assertNotContains($matchingDisabled->id()->value(), $foundIds);
        $this->assertNotContains($matchingDeleted->id()->value(), $foundIds);
        $this->assertNotContains($nonMatching->id()->value(), $foundIds);
    }

    public function testFindByFiltersExcludesDeletedSubscriptions(): void
    {
        $active = WebhookSubscriptionMother::create(name: 'Active subscription '.uniqid());
        $deleted = WebhookSubscriptionMother::create(name: 'Deleted subscription '.uniqid());
        $deleted->delete();

        $this->repository->save($active);
        $this->repository->save($deleted);

        $filters = new Filters([], Order::default(), Pagination::fromRequest(1, 100));
        $found = $this->repository->findByFilters($filters);
        $foundIds = array_map(static fn ($s) => $s->id()->value(), $found);

        $this->assertContains($active->id()->value(), $foundIds);
        $this->assertNotContains($deleted->id()->value(), $foundIds);
    }
}
