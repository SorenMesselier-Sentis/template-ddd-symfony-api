<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Notification\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Notification\InAppNotification;
use App\Shared\Domain\RealTime\RealtimePublisherInterface;
use App\Shared\Infrastructure\Notification\Handler\InAppChannelNotificationHandler;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InAppChannelNotificationHandlerTest extends TestCase
{
    #[Test]
    public function itPublishesTheNotificationToTheRecipientsPrivateTopic(): void
    {
        $recipientId = UserId::random();
        $notification = new InAppNotification($recipientId, 'Welcome', 'Hello there');

        $publisher = $this->createMock(RealtimePublisherInterface::class);
        $publisher->expects(self::once())
            ->method('publish')
            ->with(
                sprintf('/users/%s/notifications', $recipientId->value()),
                ['subject' => 'Welcome', 'body' => 'Hello there'],
            );

        $handler = new InAppChannelNotificationHandler($this->createStub(LoggerInterface::class), $publisher);
        $handler->handle($notification);
    }
}
