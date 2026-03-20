<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\BUS\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Event\UserCreated;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;

final class User

{
    /**@var DomainEvent[] */
    private array $domainEvents = [];

    private function __construct(
        private readonly UserId $id,
        private UserName $firstName,
        private UserName $lastName,
        private Email $email,
        private HashedPassword $password,
    ) {}

    public static function create(
        UserId $id,
        UserName $firstName,
        UserName $lastName,
        Email $email,
        HashedPassword $password,
    ): self {
        $user = new self($id, $firstName, $lastName, $email, $password);

        $user->record(new UserCreated(
            aggregateId: $id->value(),
            firstName: $firstName->value(),
            lastName: $lastName->value(),
            email: $email->value(),
        ));

        return $user;
    }

    public function updateName(UserName $firstName, UserName $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function updateEmail(HashedPassword $password): void
    {
        $this->password = $password;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function firstName(): UserName
    {
        return $this->firstName;
    }

    public function lastName(): UserName
    {
        return $this->lastName;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }
}
