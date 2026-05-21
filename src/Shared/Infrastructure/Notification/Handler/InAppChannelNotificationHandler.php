<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Notification\InAppNotification;
use App\Shared\Domain\Notification\Notification;
use App\Shared\Domain\Notification\NotificationChannel;
use App\Shared\Infrastructure\Notification\NotificationChannelHandler;

final class InAppChannelNotificationHandler implements NotificationChannelHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Notification $notification): void
    {
        if (!$notification instanceof InAppNotification) {
            throw new \InvalidArgumentException(sprintf(
                'Expected %s, got %s.',
                InAppNotification::class,
                $notification::class,
            ));
        }

        $this->logger->info('In-app notification dispatched', [
            'recipientId' => $notification->recipientId()->value(),
            'subject' => $notification->subject(),
        ]);
    }

    public function supports(): NotificationChannel
    {
        return NotificationChannel::IN_APP;
    }
}
