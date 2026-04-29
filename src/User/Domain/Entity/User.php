<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Event\UserCreated;
use App\User\Domain\Event\UserDeleted;
use App\User\Domain\Event\UserReplaced;
use App\User\Domain\Event\UserUpdated;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserStatus;
use DateTimeImmutable;

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
        private UserStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        UserId $id,
        UserName $firstName,
        UserName $lastName,
        Email $email,
        HashedPassword $password,
    ): self {
        $now = new DateTimeImmutable();
        $user = new self(
            id: $id,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            password: $password,
            status: UserStatus::ACTIVE,
            createdAt: $now,
            updatedAt: $now,
        );

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
        $this->touch();

        $this->record(new UserUpdated($this->id->value()));
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->touch();

        $this->record(new UserUpdated($this->id->value()));
    }

    public function updatePassword(HashedPassword $password): void
    {
        $this->password = $password;
        $this->touch();

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
        $this->touch();

        $this->record(new UserReplaced($this->id->value()));
    }

    public function delete(): void
    {
        $this->status = UserStatus::DELETED;
        $this->touch();

        $this->record(new UserDeleted($this->id->value()));
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

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // ====================================
    // GETTERS & SETTERS
    // ====================================

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

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
