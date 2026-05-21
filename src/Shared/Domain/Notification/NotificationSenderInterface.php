<?php

declare(strict_types=1);

namespace App\Shared\Domain\Notification;

interface NotificationSenderInterface
{
    public function send(Notification $notification): void;
}
