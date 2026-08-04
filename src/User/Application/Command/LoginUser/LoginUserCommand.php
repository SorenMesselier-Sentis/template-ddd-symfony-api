<?php

declare(strict_types=1);

namespace App\User\Application\Command\LoginUser;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\ValueObject\Email;

/** @implements Command<LoginUserResponse> */
final class LoginUserCommand implements Command, AuditableMessage
{
    public function __construct(
        public readonly Email $email,
        public readonly string $password,
    ) {
    }

    public function auditAction(): string
    {
        return 'user.logged_in';
    }

    public function auditTargetId(): string
    {
        return $this->email->value();
    }

    public function auditContext(): array
    {
        return [];
    }
}
