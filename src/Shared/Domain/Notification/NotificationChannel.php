<?php

declare(strict_types=1);

namespace App\Shared\Domain\Notification;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case IN_APP = 'in_app';
}
