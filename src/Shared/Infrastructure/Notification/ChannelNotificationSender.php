<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Shared\Domain\Exception\UnsupportedChannelException;
use App\Shared\Domain\Notification\Notification;
use App\Shared\Domain\Notification\NotificationSenderInterface;

final class ChannelNotificationSender implements NotificationSenderInterface
{
    /** @var array<string, NotificationChannelHandler> */
    private array $handlers = [];

    /**
     * @param iterable<NotificationChannelHandler> $handlers
     */
    public function __construct(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->supports()->value] = $handler;
        }
    }

    public function send(Notification $notification): void
    {
        $channel = $notification->channel()->value;

        if (!isset($this->handlers[$channel])) {
            throw new UnsupportedChannelException($notification->channel());
        }

        $this->handlers[$channel]->handle($notification);
    }
}
