<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Webhook\Application\Command\CreateWebhookSubscription\CreateWebhookSubscriptionCommand;
use App\Webhook\Application\Command\CreateWebhookSubscription\CreateWebhookSubscriptionCommandHandler;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateWebhookSubscriptionCommandHandlerTest extends UnitTestCase
{
    public function testItCreatesAWebhookSubscriptionAndReturnsThePlainSecretOnce(): void
    {
        /** @var WebhookSubscriptionRepositoryInterface&MockObject $repository */
        $repository = $this->createMock(WebhookSubscriptionRepositoryInterface::class);
        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $handler = new CreateWebhookSubscriptionCommandHandler($repository, $eventBus, $logger);

        $id = WebhookSubscriptionId::random()->value();
        $command = new CreateWebhookSubscriptionCommand(
            id: $id,
            name: 'Billing integration',
            url: 'https://example.com/inbound',
            eventNames: ['document.uploaded'],
        );

        $repository->expects($this->once())->method('save');
        $eventBus->expects($this->once())->method('publish');

        $result = ($handler)($command);

        $this->assertSame($id, $result['id']);
        $this->assertNotSame('', $result['secret']);
        $this->assertSame(64, \strlen($result['secret']));
    }
}
