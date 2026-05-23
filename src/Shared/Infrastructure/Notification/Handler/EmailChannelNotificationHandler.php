<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification\Handler;

use App\Shared\Domain\Email\EmailSenderInterface;
use App\Shared\Domain\Notification\EmailNotification;
use App\Shared\Domain\Notification\Notification;
use App\Shared\Domain\Notification\NotificationChannel;
use App\Shared\Infrastructure\Notification\NotificationChannelHandler;

final class EmailChannelNotificationHandler implements NotificationChannelHandler
{
    public function __construct(
        private readonly EmailSenderInterface $emailSender,
    ) {
    }

    public function handle(Notification $notification): void
    {
        if (!$notification instanceof EmailNotification) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', EmailNotification::class, $notification::class));
        }

        $this->emailSender->send($notification->toEmailMessage());
    }

    public function supports(): NotificationChannel
    {
        return NotificationChannel::EMAIL;
    }
}
