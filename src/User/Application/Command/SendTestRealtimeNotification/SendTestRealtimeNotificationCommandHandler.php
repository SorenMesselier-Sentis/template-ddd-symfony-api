<?php

declare(strict_types=1);

namespace App\User\Application\Command\SendTestRealtimeNotification;

use App\Shared\Domain\Notification\InAppNotification;
use App\Shared\Domain\Notification\NotificationSenderInterface;
use App\User\Domain\Security\UserContextInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class SendTestRealtimeNotificationCommandHandler
{
    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly NotificationSenderInterface $notificationSender,
    ) {
    }

    public function __invoke(SendTestRealtimeNotificationCommand $command): void
    {
        $this->notificationSender->send(new InAppNotification(
            recipientId: $this->userContext->userId(),
            subject: 'Realtime test notification',
            body: $command->message,
        ));
    }
}
