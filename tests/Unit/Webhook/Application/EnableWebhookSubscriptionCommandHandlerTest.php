<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Application\Command\EnableWebhookSubscription\EnableWebhookSubscriptionCommand;
use App\Webhook\Application\Command\EnableWebhookSubscription\EnableWebhookSubscriptionCommandHandler;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionStatus;
use PHPUnit\Framework\MockObject\MockObject;

final class EnableWebhookSubscriptionCommandHandlerTest extends UnitTestCase
{
    private WebhookSubscriptionRepositoryInterface&MockObject $repository;
    private EnableWebhookSubscriptionCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebhookSubscriptionRepositoryInterface::class);

        $this->handler = new EnableWebhookSubscriptionCommandHandler(
            $this->repository,
            $this->createStub(EventBusInterface::class),
        );
    }

    public function testItEnablesADisabledSubscription(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $entity->disable();
        $command = new EnableWebhookSubscriptionCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');

        ($this->handler)($command);

        $this->assertSame(WebhookSubscriptionStatus::ACTIVE, $entity->status());
    }

    public function testItThrowsWhenTheSubscriptionIsNotFound(): void
    {
        $this->expectException(WebhookSubscriptionNotFoundException::class);

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)(new EnableWebhookSubscriptionCommand(id: WebhookSubscriptionId::random()->value()));
    }
}
