<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use App\Shared\Domain\Notification\NotificationChannel;

final class UnsupportedChannelException extends InvalidArgumentException
{
    public function __construct(NotificationChannel $channel)
    {
        parent::__construct(
            sprintf(
                'No handler registered for notification channel "%s".',
                $channel->value,
            )
        );
    }

    public function errorCode(): string
    {
        return 'notification.unsupported_channel';
    }
}
