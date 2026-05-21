<?php

declare(strict_types=1);

namespace App\Shared\Domain\Notification;

use App\Shared\Domain\ValueObject\Email;

final readonly class EmailNotification implements Notification
{
    public function __construct(
        private Email $recipientEmail,
        private string $subject,
        private string $body,
    ) {
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::EMAIL;
    }

    public function recipientEmail(): Email
    {
        return $this->recipientEmail;
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
