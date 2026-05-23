<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Email;

final class UserEmailTemplate
{
    public const WELCOME = 'user/welcome';

    public const ACCOUNT_DELETION = 'user/account_deletion';
}
