<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Application\Command\DeliverWebhook\DeliverWebhookCommand;
use App\Webhook\Domain\Event\WebhookSubscriptionCreated;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Infrastructure\EventHandler\DispatchWebhooksOnAnyDomainEvent;
use PHPUnit\Framework\MockObject\MockObject;

final class DispatchWebhooksOnAnyDomainEventTest extends UnitTestCase
{
    private WebhookSubscriptionRepositoryInterface&MockObject $repository;
    private CommandBusInterface&MockObject $commandBus;
    private DispatchWebhooksOnAnyDomainEvent $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebhookSubscriptionRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->listener = new DispatchWebhooksOnAnyDomainEvent($this->repository, $this->commandBus);
    }

    public function testItDispatchesADeliveryCommandForEachMatchingActiveSubscription(): void
    {
        $subscriptionOne = WebhookSubscriptionMother::create(eventNames: ['webhook_subscription.created']);
        $subscriptionTwo = WebhookSubscriptionMother::create(eventNames: ['webhook_subscription.created']);
        $event = new WebhookSubscriptionCreated(aggregateId: 'aggregate-id');

        $this->repository->expects($this->once())
            ->method('findActiveByEventName')
            ->with('webhook_subscription.created')
            ->willReturn([$subscriptionOne, $subscriptionTwo]);

        $dispatched = [];
        $this->commandBus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Command $command) use (&$dispatched) {
                $dispatched[] = $command;

                return null;
            });

        ($this->listener)($event);

        $this->assertCount(2, $dispatched);
        $this->assertContainsOnlyInstancesOf(DeliverWebhookCommand::class, $dispatched);
        $this->assertSame($subscriptionOne->id()->value(), $dispatched[0]->subscriptionId);
        $this->assertSame($subscriptionTwo->id()->value(), $dispatched[1]->subscriptionId);
        $this->assertSame($event->eventId(), $dispatched[0]->eventId);
        $this->assertSame('webhook_subscription.created', $dispatched[0]->eventName);
    }

    public function testItDoesNothingWhenNoSubscriptionMatchesTheEvent(): void
    {
        $event = new WebhookSubscriptionCreated(aggregateId: 'aggregate-id');

        $this->repository->expects($this->once())
            ->method('findActiveByEventName')
            ->with('webhook_subscription.created')
            ->willReturn([]);

        $this->commandBus->expects($this->never())->method('dispatch');

        ($this->listener)($event);
    }
}
