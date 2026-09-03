<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Application\Command\DeleteWebhookSubscription\DeleteWebhookSubscriptionCommand;
use App\Webhook\Application\Command\DeleteWebhookSubscription\DeleteWebhookSubscriptionCommandHandler;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionStatus;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteWebhookSubscriptionCommandHandlerTest extends UnitTestCase
{
    private WebhookSubscriptionRepositoryInterface&MockObject $repository;
    private DeleteWebhookSubscriptionCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebhookSubscriptionRepositoryInterface::class);

        $this->handler = new DeleteWebhookSubscriptionCommandHandler(
            $this->repository,
            $this->createStub(EventBusInterface::class),
        );
    }

    public function testItSoftDeletesTheSubscription(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $command = new DeleteWebhookSubscriptionCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');

        ($this->handler)($command);

        $this->assertSame(WebhookSubscriptionStatus::DELETED, $entity->status());
    }

    public function testItThrowsWhenTheSubscriptionIsNotFound(): void
    {
        $this->expectException(WebhookSubscriptionNotFoundException::class);

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)(new DeleteWebhookSubscriptionCommand(id: WebhookSubscriptionId::random()->value()));
    }
}
