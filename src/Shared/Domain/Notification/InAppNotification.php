<?php

declare(strict_types=1);

namespace App\Shared\Domain\Notification;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class InAppNotification implements Notification
{
    public function __construct(
        private Uuid $recipientId,
        private string $subject,
        private string $body,
    ) {
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::IN_APP;
    }

    public function recipientId(): Uuid
    {
        return $this->recipientId;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }
}
