<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Notification\InAppNotification;
use App\Shared\Domain\Notification\Notification;
use App\Shared\Domain\Notification\NotificationSenderInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendTestRealtimeNotification\SendTestRealtimeNotificationCommand;
use App\User\Application\Command\SendTestRealtimeNotification\SendTestRealtimeNotificationCommandHandler;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\MockObject\MockObject;

final class SendTestRealtimeNotificationCommandHandlerTest extends UnitTestCase
{
    /** @var UserContextInterface&MockObject */
    private UserContextInterface $userContext;

    /** @var NotificationSenderInterface&MockObject */
    private NotificationSenderInterface $notificationSender;

    private SendTestRealtimeNotificationCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->notificationSender = $this->createMock(NotificationSenderInterface::class);
        $this->handler = new SendTestRealtimeNotificationCommandHandler(
            $this->userContext,
            $this->notificationSender,
        );
    }

    public function testItSendsAnInAppNotificationToTheCurrentUser(): void
    {
        $userId = UserId::random();

        $this->userContext->expects($this->once())->method('userId')->willReturn($userId);
        $this->notificationSender->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Notification $notification) use ($userId): bool {
                $this->assertInstanceOf(InAppNotification::class, $notification);
                $this->assertSame($userId, $notification->recipientId());
                $this->assertSame('Hello from Mercure!', $notification->body());

                return true;
            }));

        ($this->handler)(new SendTestRealtimeNotificationCommand('Hello from Mercure!'));
    }
}
