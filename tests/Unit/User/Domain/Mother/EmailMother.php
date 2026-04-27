<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Mother;

use App\Shared\Domain\ValueObject\Email;

final class EmailMother
{
    public function random(): Email
    {
        return Email::fromString(sprintf('user-%s@example.com', uniqid()));
    }

    public function create(string $value): Email
    {
        return Email::fromString($value);
    }
}
