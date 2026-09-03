<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Application\Command\DisableWebhookSubscription\DisableWebhookSubscriptionCommand;
use App\Webhook\Application\Command\DisableWebhookSubscription\DisableWebhookSubscriptionCommandHandler;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionStatus;
use PHPUnit\Framework\MockObject\MockObject;

final class DisableWebhookSubscriptionCommandHandlerTest extends UnitTestCase
{
    private WebhookSubscriptionRepositoryInterface&MockObject $repository;
    private DisableWebhookSubscriptionCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebhookSubscriptionRepositoryInterface::class);

        $this->handler = new DisableWebhookSubscriptionCommandHandler(
            $this->repository,
            $this->createStub(EventBusInterface::class),
        );
    }

    public function testItDisablesTheSubscription(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $command = new DisableWebhookSubscriptionCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');

        ($this->handler)($command);

        $this->assertSame(WebhookSubscriptionStatus::DISABLED, $entity->status());
    }

    public function testItThrowsWhenTheSubscriptionIsNotFound(): void
    {
        $this->expectException(WebhookSubscriptionNotFoundException::class);

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)(new DisableWebhookSubscriptionCommand(id: WebhookSubscriptionId::random()->value()));
    }
}
