<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Application\Command\UpdateWebhookSubscription\UpdateWebhookSubscriptionCommand;
use App\Webhook\Application\Command\UpdateWebhookSubscription\UpdateWebhookSubscriptionCommandHandler;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateWebhookSubscriptionCommandHandlerTest extends UnitTestCase
{
    private WebhookSubscriptionRepositoryInterface&MockObject $repository;
    private UpdateWebhookSubscriptionCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebhookSubscriptionRepositoryInterface::class);

        $this->handler = new UpdateWebhookSubscriptionCommandHandler(
            $this->repository,
            $this->createStub(EventBusInterface::class),
        );
    }

    public function testItUpdatesNameUrlAndEventNames(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $command = new UpdateWebhookSubscriptionCommand(
            id: $entity->id()->value(),
            name: 'New name',
            url: 'https://new.example.com/inbound',
            eventNames: ['task.created'],
        );

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');

        ($this->handler)($command);

        $this->assertSame('New name', $entity->name());
        $this->assertSame('https://new.example.com/inbound', $entity->url()->value());
        $this->assertSame(['task.created'], $entity->eventNames());
    }

    public function testItThrowsWhenTheSubscriptionIsNotFound(): void
    {
        $this->expectException(WebhookSubscriptionNotFoundException::class);

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)(new UpdateWebhookSubscriptionCommand(
            id: WebhookSubscriptionId::random()->value(),
            name: 'New name',
            url: 'https://new.example.com/inbound',
            eventNames: ['task.created'],
        ));
    }
}
