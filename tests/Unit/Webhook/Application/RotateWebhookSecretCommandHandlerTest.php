<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Application\Command\RotateWebhookSecret\RotateWebhookSecretCommand;
use App\Webhook\Application\Command\RotateWebhookSecret\RotateWebhookSecretCommandHandler;
use App\Webhook\Domain\Exception\WebhookSubscriptionNotFoundException;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use PHPUnit\Framework\MockObject\MockObject;

final class RotateWebhookSecretCommandHandlerTest extends UnitTestCase
{
    private WebhookSubscriptionRepositoryInterface&MockObject $repository;
    private RotateWebhookSecretCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebhookSubscriptionRepositoryInterface::class);

        $this->handler = new RotateWebhookSecretCommandHandler(
            $this->repository,
            $this->createStub(EventBusInterface::class),
        );
    }

    public function testItRotatesTheSecretAndReturnsItOnce(): void
    {
        $entity = WebhookSubscriptionMother::create(secret: 'old-secret');
        $command = new RotateWebhookSecretCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->handler)($command);

        $this->assertNotSame('old-secret', $result['secret']);
        $this->assertSame($result['secret'], $entity->secret());
        $this->assertSame(64, \strlen($result['secret']));
    }

    public function testItThrowsWhenTheSubscriptionIsNotFound(): void
    {
        $this->expectException(WebhookSubscriptionNotFoundException::class);

        $this->repository->expects($this->once())->method('findById')->willReturn(null);

        ($this->handler)(new RotateWebhookSecretCommand(id: WebhookSubscriptionId::random()->value()));
    }
}
