<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Event\UserCreated;
use App\User\Domain\Event\UserReplaced;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\Event\UserUpdated;

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

    public static function delete(): void {}

    public function updateName(UserName $firstName, UserName $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;

        $this->record(new UserUpdated($this->id->value()));
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;

        $this->record(new UserUpdated($this->id->value()));
    }

    public function updatePassword(HashedPassword $password): void
    {
        $this->password = $password;

        $this->record(new UserUpdated($this->id->value()));
    }

    public function replace(
        UserName $firstName,
        UserName $lastName,
        Email $email,
        HashedPassword $password,
    ): void {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;

        $this->record(new UserReplaced($this->id->value()));
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
