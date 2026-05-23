<?php

declare(strict_types=1);

namespace App\Shared\Domain\Notification;

interface Notification
{
    public function channel(): NotificationChannel;

    public function subject(): string;

    public function body(): string;
}
