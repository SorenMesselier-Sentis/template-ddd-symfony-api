<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUser;

use App\Shared\Domain\Bus\Query\Response;
use App\User\Domain\Entity\User;

final class UserResponse implements Response
{
    public readonly string $id;
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $email;
    public readonly string $status;
    /** @var list<string> */
    public readonly array $roles;
    public readonly bool $emailVerified;
    public readonly string $createdAt;
    public readonly string $updatedAt;

    public function __construct(User $user)
    {
        $this->id = $user->id()->value();
        $this->firstName = $user->firstName()->value();
        $this->lastName = $user->lastName()->value();
        $this->email = $user->email()->value();
        $this->status = $user->status()->value;
        $this->roles = array_map(static fn ($role) => $role->value, $user->roles());
        $this->emailVerified = $user->isEmailVerified();
        $this->createdAt = $user->createdAt()->format(\DateTimeInterface::ATOM);
        $this->updatedAt = $user->updatedAt()->format(\DateTimeInterface::ATOM);
    }
}
