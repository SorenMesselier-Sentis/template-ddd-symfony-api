<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Shared\Domain\Notification\Notification;
use App\Shared\Domain\Notification\NotificationChannel;

interface NotificationChannelHandler
{
    public function supports(): NotificationChannel;

    public function handle(Notification $notification): void;
}
